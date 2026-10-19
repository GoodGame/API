# IconService — пользовательская документация

Оформление сообщений в чате. Для **каждого входящего сообщения** чата нужно определить три вещи по его автору:

- **цветовую рамку/ник** — `getColor(user, data)`;
- **пиктограмму рядом с ником** — `getIcon(user, data)`;
- **подпись роли** — `getRole(user)`.

Это обычные JavaScript-функции: они не рисуют ничего сами, а возвращают **строковые идентификаторы** (`'gold'`, `'star'`, `'streamer'` и т.д.), которые вы превращаете в CSS-классы / svg-иконки своими стилями и на своём стеке.

---

## Как приходят сообщения в чате

Каждое сообщение чата приходит вместе с **объектом автора** (`user`) и, опционально, со словарём переопределений (`data`) от комнаты/сообщения. Поток такой:

1. В чат приходит новое сообщение. Вместе с текстом передаётся служебная нагрузка:
   - `message.user` — данные автора на момент отправки (цвет, иконка, донат, права, премиум и т.д. — все поля из таблицы ниже);
   - иногда отдельно `message.data` (или эквивалентное поле контекста) — переопределения цвета/иконки от комнаты или типа сообщения.
2. Для каждого сообщения вы **один раз** вызываете `getColor(user, data)` и `getIcon(user, data)` и результат превращаете в классы в строке сообщения.
3. Для системных/служебных строк (вход в чат, бан, донат-алерты) те же вызовы выполняются по объекту автора события, если он есть.
4. Результаты считаются на лету: объект автора может обновиться из следующего события (сменился донат, модератор выдал цвет), поэтому кэшировать результат оформления не нужно — просто пересчитывать при отрисовке/обновлении строки.

Практически: держите список сообщений, у каждого храните ссылку на автора (или снимок его полей), и при рендере строки делайте:

```js
var color = getColor(message.user, message.data); // например 'gold'
var icon  = getIcon(message.user, message.data);  // например 'eagle'
var role  = getRole(message.user);                // например 'moderator'
```

## Ожидаемые объекты

### `user` (объект автора сообщения)

| Поле | Тип | Назначение |
|---|---|---|
| `color` | string | Выбранный пользователем цвет ника |
| `icon` | string | Выбранная пользователем иконка |
| `regtime` | number | Unix-время регистрации (секунды). Нужен для «новичка» |
| `staff` | boolean | Признак сотрудника (администрация проекта) |
| `lawyer` | boolean | Признак «юриста» |
| `rights` | number | Уровень прав: 10, 20, 30, 40, 50 … |
| `payments` | number | Уровень доната — **индекс** в списке платёжных уровней (0..10) |
| `roomPremium` | boolean | Premium в конкретной комнате |
| `commonPremium` | boolean | Общий премиум |
| `premium` | boolean | (только для проверки иконки `star`) |
| `ggPlusTier` | number | Уровень GG+ подписки; `-1`/не задан = нет |
| `mobile` | number | Клиент, с которого пишут: `1` phone, `2` iOS, `3` Windows, `4` Android |
| `roomid` | number/string | ID текущей комнаты |
| `inviteLevels` | array | Уровни прогресса по приглашениям (нужен `inviteLevels[0]`) |
| `role` | string | Явно заданная роль |
| `settings.role` | string | Роль из объекта настроек |
| `settings.customColors` | object | Кастомные цвета по комнатам: `{ [roomid]: colorName }` |
| `settings.customIcons` | object | Кастомные иконки по комнатам: `{ [roomid]: iconName }` |

### `data` (необязательный второй аргумент)

Переопределение от сообщения/комнаты: `data.color` и `data.icon` используются только если у самого `user` пусто (`user.color || data.color`).

---

## Цвета (`getColor`)

Полный алгоритм по порядку — первый совпавший результат возвращается:

1. **Цвет по правам** (перекрывает почти всё): если `user.staff` ложь и `rights` есть в таблице
   - `10`, `20` → `streamer`
   - `30`, `40`, `50` → `moderator`
   — вернуть его. (У сотрудников со `staff = true` этот шаг пропускается.)

2. **Новичок**: если с момента `regtime` прошло меньше **86400 секунд (24 часа)** → `newguy`.
   Важно: донат не меняет цвет новичка — даже донативший в первые сутки остаётся `newguy`.

3. **Выбранный пользователем цвет** (`user.color || data.color`), но только если он «доступен» (проверка `_isColorAvaiable` см. ниже).

4. **Каскад по умолчанию** (первый непустой):
   1. кастомный цвет комнаты `settings.customColors[user.roomid]`;
   2. цвет по правам — для интеграции вычисляйте таблицей из шага 1 по `user.rights` (в дефолтном каскаде выполняется только для сотрудников, см. раздел «Особенности»);
   3. `paymentsColors[user.payments]` — цвет за уровень доната;
   4. `roomPremium` → `premium-personal`;
   5. `commonPremium` → `premium`;
   6. иначе → `simple`.

### Итоговый список цветовых идентификаторов

| Идентификатор | За что даётся |
|---|---|
| `streamer` | rights 10 / 20 |
| `moderator` | rights 30 / 40 / 50 |
| `newguy` | регистрация младше 24 часов |
| `simple` | по умолчанию |
| `premium` | общий премиум |
| `premium-personal` | премиум комнаты |
| `bronze`, `silver`, `gold`, `diamond`, `king`, `undead` | донат-уровни 1–6 |
| `gradient-fire`, `gradient-cherry`, `gradient-mushroom` | донат-уровни 7–9 |
| `top-one` | донат-уровень 10 |

```js
// канонический список донат-цветов (индекс = user.payments)
var paymentsColors = ['', 'bronze', 'silver', 'gold', 'diamond', 'king', 'undead',
    'gradient-fire', 'gradient-cherry', 'gradient-mushroom', 'top-one'];
```

---

## Иконки (`getIcon`)

Алгоритм по порядку:

1. **Клиент**: `mobile === 1/2/3/4` → `phone` / `ios` / `win` / `android` (безусловный приоритет).

2. **Выбранная пользователем иконка** (`user.icon || data.icon`), если она разрешена (_isIconAvaiable_, см. ниже).

3. **Каскад по умолчанию** (первый непустой):
   1. кастомная иконка комнаты `settings.customIcons[user.roomid]`;
   2. `staff` → `staff`;
   3. `lawyer` → `lawyer`;
   4. иконка по правам: `10` → `helper`; `30/40/50` → `moderator`;
   5. иконка по донату `paymentsIcons[user.payments]`;
   6. `roomPremium` → `star`;
   7. `ggPlusTier >= 0` → `ggplus`;
   8. `commonPremium` → `star`;
   9. иначе → `none`.

### Итоговый список иконок

| Идентификатор | За что даётся |
|---|---|
| `phone` / `ios` / `win` / `android` | тип клиента |
| `staff` | сотрудник |
| `lawyer` | юристы |
| `helper` | rights 10 |
| `moderator` | rights 30+ |
| `coin`, `eagle`, `cup`, `diamond`, `crown`, `undead`, `fire`, `cherry`, `mushroom`, `top1` | донат-уровни 1–10 |
| `star` | room/common premium |
| `ggplus` | любой GG+ уровень (tier ≥ 0, включая 0). В разметке идёт с подтипом `ggplus-N` (N = число ресабов), см. SCSS |
| `invite`, `invite-1`, `invite-2`, `invite-3` | приглашения (уровень `inviteLevels[0]`) |
| `none` | по умолчанию (рисовать нечего) |

```js
// канонический список донат-иконок (индекс = user.payments)
var paymentsIcons = ['', 'coin', 'eagle', 'cup', 'diamond', 'crown', 'undead',
    'fire', 'cherry', 'mushroom', 'top1'];
// уровни приглашений
var inviteIcons = ['invite', 'invite-1', 'invite-2', 'invite-3'];
```

---

## Подписи роли (`getRole`)

Подпись («стриммер», «модератор» и т.п.) отображается только при `ggPlusTier >= 7` — это условие **первое и обязательное**:

1. если `ggPlusTier < 7` → пустая строка;
2. если задан `user.role` → он;
3. если есть `user.settings` → `user.settings.role`;
4. иначе пустая строка.

---

## Проверки «доступности» выбранного оформления

Пользователь через настройки может выбрать цвет/иконку, но функции следят, чтобы нельзя было самовольно присвоить себе чужой уровень.

### Доступность цвета (_isColorAvaiable_)

Белый список: всё из `paymentsColors` + фиксированные `simple, premium, premium-personal, streamer, moderator`. Всего остальное — отказ.

Условия для конкретных значений:

| Цвет | Условие |
|---|---|
| `simple` | всегда |
| `premium` | нужен `commonPremium` |
| `premium-personal` | нужен `roomPremium` |
| `streamer` | нужен `rights >= 20` |
| `moderator` | нужен `rights >= 30` |
| донат-цвет | его **индекс** в `paymentsColors` должен быть ≤ `user.payments` |

> Заметьте: `paymentsColors` начинается с пустой строки `''`, поэтому «индекс выше 0» означает «как минимум бронза».

### Доступность иконки (_isIconAvaiable_)

Белый список: объединение `['none','staff','lawyer','star','moderator','ggplus']` + `paymentsIcons` + `inviteIcons`.

| Иконка | Условие |
|---|---|
| `none` | всегда |
| `staff` | нужен `user.staff` |
| `lawyer` | нужен `user.lawyer` |
| `star` | нужен `user.premium` |
| `moderator` | нужен `rights > 20` |
| `ggplus` | разрешено при `ggPlusTier > 0` (в дефолтном каскаде порог мягче — `>= 0`, см. «Особенности») |
| `invite*` | цифра в имени (`invite-2` → 2) должна быть ≤ `user.inviteLevels[0]`; у самого `invite` цифры нет |
| донат-иконка | индекс в `paymentsIcons` ≤ `user.payments` |

---

## SCSS для цветов и иконок

Функции возвращают только имена классов, поэтому вся визуализация живёт в CSS. Ниже рабочий каркас под структуру разметки из примера интеграции (назад к строке сообщения вешаются `color`+`icon`, внутри неё — `<i class="icon icon-…">` и ника). Значения цветов соответствуют актуальным стилям goodgame.ru — подгоняйте под свою палитру.

```scss
// ---- Палитра ников (значения — как на goodgame.ru) ------------------------
$nick-colors: (
    // простые цвета
    simple:          #75adfc,
    premium:         #bd70d7,
    premium-personal:#31a93a,
    moderator:       #ec4058,
    streamer:        #e8bb00,
    // донат-уровни 1..10 (индекс = user.payments)
    bronze:          #e7820a,
    silver:          #b4b4b4,
    gold:            #eefc08,
    diamond:         #8781bd,
    king:            #30d5c8,
    undead:          #ab4873,
    top-one:         #3bcbff,
    newguy:          #ffffff
);

// ---- Список донат-уровней (для цикла по иконкам) --------------------------
$donate-icon-list: coin, eagle, cup, diamond, crown, undead, fire, cherry, mushroom, top1;

// ---- База иконок: полный url с доменом ---------------------------------------
$icon-base: 'https://goodgame.ru/images/chat-svg-icons';

// ---- Базовый каркас строки -------------------------------------------------
.chat-message {
  .nick { color: #75adfc; }

  // Простой цвет через CSS-переменную — проще, чем дублировать правила
  .nick {
    @each $name, $color in $nick-colors {
      &.#{$name} {
        color: $color;
        .icon::before { background-color: $color; }
      }
    }

    // Новичок выделяется и жирностью
    &.newguy { font-weight: 600; }
  }

  // ---- Градиенты (донат-уровни 7..9): текст с градиентной заливкой --------
  .nick.gradient-mushroom, .nick.gradient-cherry, .nick.gradient-fire {
    background-color: #fff;
    background-repeat: repeat;
    background-size: 100%;
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .nick.gradient-mushroom { background-image: linear-gradient(90deg, #bababa 0%, #ab4873 100%); }
  .nick.gradient-cherry   { background-image: linear-gradient(90deg, #ea455c 0%, #35a83f 100%); }
  .nick.gradient-fire     { background-image: linear-gradient(90deg, #ec8c24 0%, #f2f932 100%); }

  // У градиентных ников иконка не красится в цвет ника — рисуется своим svg
  .gradient-mushroom .icon::before,
  .gradient-cherry   .icon::before,
  .gradient-fire     .icon::before {
    background-color: transparent;
  }
}

// ---- Иконки: каждая = маска svg + цвет от ника ------------------------------
// Принцип: <i class="icon icon-<имя>"> получает ::before с маской;
// цвет наследуется от ника через background-color (красится автоматически).
.chat-message .icon {
  &.icon-none { display: none; }

  &.icon-star, &.icon-diamond, &.icon-staff, &.icon-crown, &.icon-top1,
  &.icon-undead, &.icon-helper, &.icon-cup, &.icon-coin, &.icon-eagle,
  &.icon-phone, &.icon-ios, &.icon-win, &.icon-android, &.icon-lawyer,
  &.icon-fire, &.icon-cherry, &.icon-mushroom, &.icon-moderator,
  &.icon-ggplus, &.icon-invite, &.icon-invite-1, &.icon-invite-2, &.icon-invite-3 {
    &::before {
      content: '';
      display: inline-block;
      width: 24px;
      height: 24px;
      mask-size: contain;
      mask-repeat: no-repeat;
      background-size: contain;
      background-repeat: no-repeat;
    }
  }

  // маппинг: имя иконки → svg-маска (полные url с доменом, через переменную $icon-base)
  &.icon-star     ::before { mask-image: url('#{$icon-base}/StarFull24px.svg'); }
  &.icon-diamond  ::before { mask-image: url('#{$icon-base}/Dimond24px.svg'); }
  &.icon-crown    ::before { mask-image: url('#{$icon-base}/Crown24px.svg'); }
  &.icon-top1     ::before { mask-image: url('#{$icon-base}/Hero24px.svg'); }
  &.icon-undead   ::before { mask-image: url('#{$icon-base}/Skull24px.svg'); }
  &.icon-helper   ::before { mask-image: url('#{$icon-base}/Helper24px.svg'); }
  &.icon-cup      ::before { mask-image: url('#{$icon-base}/Cup24px.svg'); }
  &.icon-coin     ::before { mask-image: url('#{$icon-base}/Donut24px.svg'); }
  &.icon-eagle    ::before { mask-image: url('#{$icon-base}/Eagle24px.svg'); }
  &.icon-staff    ::before { mask-image: url('#{$icon-base}/Wrench24px.svg'); }
  &.icon-phone    ::before { mask-image: url('#{$icon-base}/Phone24px.svg'); }
  &.icon-ios      ::before { mask-image: url('#{$icon-base}/Apple24px.svg'); }
  &.icon-win      ::before { mask-image: url('#{$icon-base}/Win24px.svg'); }
  &.icon-android  ::before { mask-image: url('#{$icon-base}/Android24px.svg'); }
  &.icon-lawyer   ::before { mask-image: url('#{$icon-base}/Lawyer24px.svg'); }
  &.icon-moderator::before { mask-image: url('#{$icon-base}/Sword24px.svg'); }
  &.icon-fire     ::before { mask-image: url('#{$icon-base}/Fire24px.svg'); }
  &.icon-cherry   ::before { mask-image: url('#{$icon-base}/Cherry24px.svg'); }
  &.icon-mushroom ::before { mask-image: url('#{$icon-base}/Mushroom24px.svg'); }
  &.icon-invite::before, &.icon-invite-1::before { mask-image: url('#{$icon-base}/RetroGhost24px.svg'); }
  &.icon-invite-2::before { mask-image: url('#{$icon-base}/Invader24px.svg'); }
  &.icon-invite-3::before { mask-image: url('#{$icon-base}/Tank24px.svg'); }

  // ggplus всегда идёт ПАРОЙ классов: icon-ggplus + ggplus-<подтип>,
  // где подтип = уровень GG+ (число накопленных ресабов).
  // svg-маска выбирается подтипом, поэтому у icon-ggplus самой маски нет.
  &.icon-ggplus {
    &::before { content: ''; }

    &.ggplus-1::before { mask-image: url('#{$icon-base}/gg-1-24px.svg'); }
    &.ggplus-2::before { mask-image: url('#{$icon-base}/gg-3-24px.svg'); }
    &.ggplus-3::before { mask-image: url('#{$icon-base}/gg-6-24px.svg'); }
    &.ggplus-4::before { mask-image: url('#{$icon-base}/gg-12-24px.svg'); }
    &.ggplus-5::before { mask-image: url('#{$icon-base}/gg-24-24px.svg'); }
    &.ggplus-6::before { mask-image: url('#{$icon-base}/gg-48-24px.svg'); }
    &.ggplus-7::before { mask-image: url('#{$icon-base}/gg-96-24px.svg'); }
  }
}
```

Практические заметки по стилям:

1. **Один источник правды для цвета ника и иконки.** Иконка красится в цвет ника через свой `background-color` внутри `&.#{$name} { .icon::before { background-color: $color; } }` — не нужно дублировать цвета для каждого уровня доната отдельно.
2. **Маска вместо цвета svg**: делаете svg-иконку «вырезом» (`mask-image`) и красите её в любой цвет через `background-color`. Так одна иконка автоматически получает цвет ника (бронза — бронзовая звёздочка, золото — золотая).
3. **Градиентные никлиенты** (`gradient-…`) нельзя красить через `color` — там фон с `background-clip: text`; иконкам в таких строках надо явно сбрасывать `background-color`, иначе они станут прозрачными/чёрными.
4. Url иконок — полные, с доменом (`https://goodgame.ru/images/chat-svg-icons/...`), собранные интерполяцией `url('#{$icon-base}/ИмяФайла.svg')`. SVG лежат на основном домене; если планируете хостить их у себя — поменяйте `$icon-base`, остальной код не трогается.
5. **`ggplus` — единственная иконка с подтипами**: функция возвращает просто `'ggplus'`, но число ресабов показывается вторым классом `ggplus-N` (`N = ggPlusTier`, 1..7), и svg-маска выбирается именно подтипом. Поэтому без подтипа иконка ggplus не рисуется — не забудьте подставлять его в разметке (см. пример рендера).

---

## Прогресс премиума на канал (`premium-advantages__progress-bar`)

Это тот же «язык оформления», что и выше, но в попапе «Что даёт премиум на канал» (`gg-window-advantages`): горизонтальная полоса-шкала показывает, **какая иконка в чате и какой цвет ждут подписчика на каждом сроке премиума канала** — от 1 месяца до 8 лет. Чем дольше подписка, тем правее ступенька по градиентной линии.

### Откуда данные

Данные грузятся запросом **`GET /api/4/streams/premium-settings?channel=<id или имя канала>`** и кладутся в поле `streamer`:

```js
streamer = {
    // ...
    streamer: { avatar, firstname, lastname, ... }, // аватар/имя стримера в шапке попапа
    channel:  { ... },                              // данные канала
    smiles:   ['smile1', ...],                      // ключи уникальных смайлов канала
    prices:   [ { id, months, price, baseAmount, discountPercent, pricePerMonth, ... } ],
    icons: {                                        // чат-иконки по типам подписки
        "1": { "48": "<имя>48.png", "32": "<имя>32.png", "16": "<имя>16.png" }, // 1 мес.
        "2": { "48": ..., "32": ..., "16": ... },   // 3 мес.
        "3": { "48": ..., "32": ..., "16": ... },   // полгода
        "4": { "48": ..., "32": ..., "16": ... }    // год
    }
};
// ключи «1..4» — это type подписки: 30/90/180/360 дней;
// у канала могут быть не все ключи — заводятся последовательно, без пропусков
```

Шкала-константа на 7 ступеней (метки и дефолтные картинки):

| № | Метка | Дефолтная картинка |
|---|---|---|
| 1 | 1 мес. | `/images/gg-plus/progressbar/1month.svg` |
| 2 | 3 мес. | `/images/gg-plus/progressbar/3months.svg` |
| 3 | Полгода | `/images/gg-plus/progressbar/6months.svg` |
| 4 | Год | `/images/gg-plus/progressbar/1year.svg` |
| 5 | 2 года | `/images/gg-plus/progressbar/2year.svg` |
| 6 | 4 года | `/images/gg-plus/progressbar/4year.svg` |
| 7 | 8 лет | `/images/gg-plus/progressbar/8year.svg` |

Сопоставление «иконка ↔ метка» делается **по порядку**: первые `Object.keys(streamer.icons).length` ступеней рисуют иконку с сервера (`https://goodgame.ru/files/icons/<имя файла из icons[key]['48']>`) и метку `duration[i].label`; если иконок меньше семи, оставшиеся ступени рисуют дефолтные svg-картинки из таблицы.

### Как именно подставляется иконка

Подстановка — три шага: «ключ → имя файла → полный url».

1. **Ключ ступени** — `type` подписки из API: `1` = 30 дней (1 мес.), `2` = 90 дней (3 мес.), `3` = 180 дней (полгода), `4` = 360 дней (год). API отдаёт `icons` уже размеченным по этим ключам:

```js
streamer.icons = {
    "1": { "48": "iconname48.png", "32": "iconname32.png", "16": "iconname16.png" },
    "2": { "48": "iconname48.png", "32": "iconname32.png", "16": "iconname16.png" },
    // ...
}
```

   (значение `icons[type]` — картинка в трёх размерах 48/32/16; для прогресс-бара берётся `['48']`)

2. **Разметка** берёт имя файла по ключу и собирает полный url:

```html
<div *ngFor="let key of Object.keys(streamer.icons); index as i">
    <img src="https://goodgame.ru/files/icons/{{ streamer.icons[key]['48'] }}"/>
    <p>{{duration[i].label}}</p>          <!-- метка срока по позиции i, не по ключу -->
</div>
<!-- ступени без своей иконки: дефолтные svg из duration -->
<div *ngFor="let element of duration.slice(Object.keys(streamer.icons).length)">
    <img [src]="element.url" width="48" height="48"/>
    <p>{{element.label}}</p>
</div>
```

3. **То же в чистом JS** (без шаблонизатора) — одна функция подставляет иконку на нужную ступень:

```js
var ICON_BASE = 'https://goodgame.ru/files/icons/';

// возвращает src иконки для ступени с номером step (1..7) и её метку
function stepFor(step, streamer) {
  var keys = Object.keys(streamer.icons);           // ['1', '2', ...] по порядку
  var i = step - 1;

  if (i < keys.length) {
    // ступень с серверной иконкой канала
    return {
      url:  ICON_BASE + streamer.icons[keys[i]]['48'],  // подстановка иконки
      label: duration[i].label
    };
  }

  // ступень дальше — дефолтная картинка из шкалы
  return { url: duration[i].url, label: duration[i].label };
}

// <img src="stepFor(2, streamer).url" alt="">  → https://goodgame.ru/files/icons/<имя>48.png
```

Важные правила этой подстановки:

- **Ключи `icons` — строки** (`"1"`, `"2"`…), порядок обхода `Object.keys()` даёт их по возрастанию — поэтому «первая ступень» = `type 1` (1 мес.).
- **Метка берётся по позиции**, а не по ключу: у ступени №2 подпись `duration[1].label` («3 мес.»), даже если ключ другой — связка «ключ = позиция» должна соблюдаться на стороне данных (иконки заводятся последовательно, без пропусков).
- **Размер `48`** — для попапа; при другой разметке можно взять `['32']`/`['16']` из того же объекта.
- **Расширение файла — `.png`** (иконки канала), в отличие от дефолтных ступеней, там svg.
- Если у канала иконок нет вообще (`icons` пуст), все 7 ступеней рисуются дефолтными svg — разметка не меняется.

### Разметка (десктоп)

```html
<div class="premium-advantages__progress-bar">
    <!-- ступень = картинка 48x48 + подпись под ней -->
    <div><img src="https://goodgame.ru/files/icons/..." alt=""><p>1 мес.</p></div>
    <!-- ... всего до 7 ступеней -->
</div>
```

### Стили (SCSS)

```scss
.premium-advantages__progress-bar {
  position: relative;
  height: 4px;
  width: 672px;
  margin: 0 auto 68px;

  // линия-градиент «дорогая» палитра: тёмно-багровый → изумрудный
  background: linear-gradient(90deg, #721933 0%, #1EA384 100%);

  div {
    position: absolute;
    top: -23px;          // картинка+подпись висят над линией

    img { width: 48px; height: 48px; }

    p {
      margin: 0; padding: 0;
      font-size: 12px; line-height: 22px; text-align: center;
      color: $chatGrey;                          // приглушённо-серая подпись
    }

    // фиксированные X-позиции ступеней (ступени «разжаты» к правому краю)
    &:first-child  { left: -24px; }
    &:nth-child(2) { left: 54px; }
    &:nth-child(3) { left: 132px; }
    &:nth-child(4) { left: 240px; }
    &:nth-child(5) { left: 368px; }
    &:nth-child(6) { left: 496px; }
    &:nth-child(7) { left: 648px; }
  }
}
```

Заметьте: смещения ступеней неравномерные (`-24 → 54 → 132 → 240 → 368 → 496 → 648`) — интервалы растут, чтобы короткие сроки (1–3 мес.) не слипались, а правые ступени сидели у правого края шкалы.

### Мобильная версия (`≤ 768px`)

Десктопный прогресс-бар прячется (`display: none`), вместо него включается двухрядная версия `premium-advantages__mobile-progress-bar`: те же ступени, две полосы по 84% ширины — верхняя (4 ступени: 1 мес./3 мес./полгода/год) и нижняя (3 ступени: 2/4/8 лет):

```scss
.premium-advantages__mobile-top-progress {
  position: relative;
  width: 84%; height: 4px;
  margin-bottom: 74px;
  background: linear-gradient(90deg, #721933 0%, #623443 100%); // переходная часть

  div {
    position: absolute; top: -23px;
    &:first-child  { left: -24px; }
    &:nth-child(2) { left: 20%; }
    &:nth-child(3) { left: 50%; }
    &:nth-child(4) { right: -24px; }
  }
}

.premium-advantages__mobile-bottom-progress {
  position: relative;
  width: 84%; height: 4px;
  background: linear-gradient(90deg, #623443 0%, #1EA384 100%); // зелёная часть

  div {
    position: absolute; top: -23px;
    &:first-child { left: -24px; }
    &:nth-child(2) { left: 38%; }
    &:last-child   { right: -24px; }
  }
}

@media(min-width: 769px) { .premium-advantages__mobile-progress-bar { display: none; } }
```

Позиции по `%` (`20%`, `50%`, `38%`) — мобильная шкала «резиновая», а крайние ступени выходят на `-24px` за края полосы (по ширине полукартинки).

### Разница с GG+ (без канала)

Когда попап открыт не для канала, а для общего GG+, вместо этого блока рисуется `ggplus-advantages__progress-bar` — та же разметка и те же фиксированные позиции, но:

- градиент другой: `linear-gradient(90deg, #7EB9B9 0%, #7E2595 100%)` (бирюза → фиолет);
- иконки всегда статичные — семь svg из таблицы выше (`/images/gg-plus/progressbar/*.svg`), данные из API не нужны;
- мобильная двухрядная версия та же (`premium-advantages__mobile-progress-bar`).

### Как это связано с чатом

Иконки, показанные на шкале, — это те самые чат-иконки уровня доната (`paymentsIcons`: bronze/silver/gold/…), которые потом приходят в сообщениях и раскрашиваются стилями из раздела «SCSS для цветов и иконок». То есть шкала в попапе — наглядный «прайс» на оформление из этой документации.

---

## Особенности (важно при самостоятельной реализации)

1. **Права приоритетнее всего остального в цвете.** Права проверяются первым шагом, до «новичка», кастомных и выбранных цветов; в дефолтном каскаде цвет по правам учитывается только для сотрудников. Если реализуете каскад сами — вычисляйте цвет по правам таблицей из шага 1 для пользователей со `staff` (или просто по `user.rights`): для остальных результат тот же, ведь их цвет по правам уже вернулся первым шагом при выборе пользовательского цвета.

2. **Для донат-цветов проверяется только уровень доната** — например, пользователь без `commonPremium` может выбрать донат-цвет уровня ≤ своего доната.

3. **Сотрудникам доступен широкий выбор оформления** — все цвета/иконки из белого списка, без проверки соответствия уровням.

4. **Пороги для цвета и иконки разные**: цвет `streamer` с `rights >= 20`, иконка `moderator` с `rights > 20`; иконка `helper` (rights 10) не имеет цветового аналога.

5. **Неподдерживаемые invite-иконки** (`retroghost`, `invader`, `tank`) — в белый список не входят, выбираются только `invite`, `invite-1`, `invite-2`, `invite-3`.

6. **`_getCustomColor`/`_getCustomIcon` завёрнуты в try/catch** — если у автора нет `settings`, или `customColors`/`customIcons` отсутствуют, вернётся `false`, и каскад пойдёт дальше. Делайте защитный доступ (`settings?.customColors?.[roomid]` или аналогично), чтобы не падать на неполных данных.

7. **Пустые первые элементы списков** (`''` под индексом 0) намеренные: `payments = 0` = «доната нет», и индекс служит и уровнем, и правом доступа одновременно.

---

## Рекомендуемая интеграция у себя

1. Реализуйте три чистые функции (`getColor`, `getIcon`, `getRole`) со списками выше — они не зависят от DOM и от фреймворка.

2. При получении каждого нового сообщения из транспорта чата обрабатывайте его по единой схеме:

```js
// message = { text, user, data? } — пришло из чата
function renderMessage(message) {
  var color = getColor(message.user, message.data);   // например 'gold'
  var icon  = getIcon(message.user, message.data);    // например 'eagle'
  var role  = getRole(message.user);                  // например 'moderator'

  // ggplus требует подтип: число ресабов подставляется вторым классом
  var iconClass = icon === 'ggplus'
    ? 'icon-ggplus ggplus-' + message.user.ggPlusTier
    : 'icon-' + icon;

  var el = document.createElement('div');
  el.className = 'chat-message nickname ' + color;
  el.innerHTML =
    '<i class="icon ' + iconClass + '"></i>' +
    '<span class="name">' + escapeHtml(message.user.name) + '</span>' +
    (role ? '<span class="role">' + escapeHtml(role) + '</span>' : '') +
    '<span class="text">' + escapeHtml(message.text) + '</span>';

  // добавьте el в контейнер сообщений
}
```

    Точный вид классов подберите под свои стили — функции отвечают только за имена (`gold`, `star`, `ggplus`, …). Исключение: `ggplus` дополнительно размечается подтипом `ggplus-N` (`N = user.ggPlusTier`), потому что svg-иконка зависит от числа ресабов.

3. При **обновлении** уже показанного сообщения или самого пользователя (сменился донат, модератор выдал цвет/иконку) пересчитывайте оформление заново и обновляйте классы строки — не кэшируйте результат.

4. Стилизуйте сами: например, класс `gold` → золотой градиент рамки/ника; `gradient-fire` → CSS-градиент; `star` → svg-звезда и т.д. Функции намеренно возвращают только имена.

5. Передавайте `data` (второй аргумент) только там, где сообщение/комната может перекрыть выбранные пользователем настройки.