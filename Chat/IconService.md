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