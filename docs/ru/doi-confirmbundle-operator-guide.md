# DOIConfirmBundle 2.0.3: руководство оператора

Документ описывает canonical plugin source `DOIConfirmBundle` версии `2.0.3`
для Mautic 5.x, 6.x и 7.x, включая проверенный сценарий Mautic 7.1.3.
Live-установку, demo-цепочку и приемку на `news.show-master.ru` выполняет
SalesSnap-Operation. Этот репозиторий содержит только source, документацию и
plugin-owned compatibility evidence.

![Жизненный цикл DOI](../assets/doi-lifecycle-ru.svg)

## Назначение

`DOIConfirmBundle` добавляет double opt-in процесс к обычной Mautic form.
Контакт отправляет форму, Mautic отправляет выбранное письмо с подтверждающей
ссылкой, а после клика плагин применяет настроенные действия: добавляет или
удаляет теги, добавляет или удаляет сегменты, обновляет поля контакта, снимает
email DNC, пишет audit log, регистрирует page hit и отправляет webhook event.

Главный runtime-переключатель — `Active` у integration `Doi Report`. Когда он
выключен, плагин не регистрирует form action, report/webhook события, не
принимает public DOI endpoints и не применяет queued mutations.

## Lifecycle

1. Оператор включает plugin integration `Doi Report`.
2. Оператор создает или выбирает email для DOI.
3. В письме обязательно размещается token `{doi_url}`.
4. Опционально в скрытую или не предназначенную для человека ссылку добавляется
   token `{doi_nothuman}`.
5. В форме добавляется action `Double-Opt-In (DOI) via Email Confirmation`.
6. При submit action плагин собирает DOI payload, шифрует его и отправляет DOI
   email выбранному контакту.
7. Клик по `/doi/{enc}` расшифровывает payload, восстанавливает исходный request
   context и ставит success processing в Symfony Messenger.
8. Если transport `sync://`, success actions выполняются сразу. Если настроен
   async transport, обработка может быть отложена; в plugin code используется
   задержка 60 секунд.
9. Если до обработки был открыт `/nothuman/{hash}`, handler отменяет pending
   success actions и удаляет marker.
10. Если bot-trap не сработал, handler применяет DOI success actions.

## Form action в Mautic 7.1.3

![Поля form action](../assets/doi-form-action-ru.svg)

В Mautic 7.1.3 ожидаемая последовательность настройки:

1. Откройте form.
2. Перейдите в tab или section `Actions`.
3. Добавьте submit action из группы email actions:
   `Double-Opt-In (DOI) via Email Confirmation`.
4. Заполните обязательные поля:
   `Email to send` и `Redirect URL after success`.
5. При необходимости заполните success actions:
   `Tags to set after successfull DOI`,
   `Tags to remove after successfull DOI`,
   `Segments to add to contact after successfull DOI`,
   `Segments to remove from contact after successfull DOI`,
   `Update contact fields after successfull DOI`.
6. При необходимости заполните pre-send action:
   `Update contact fields before successfull DOI`.
7. Если email может быть сохранен не в основном поле контакта, заполните
   `Lead field alias for email (optional)`.

Если UI отличается от скриншотов или перевода, ориентируйтесь на source keys и
storage names:

| Назначение | UI label key | Storage key |
| --- | --- | --- |
| Выбор DOI email | `mautic.email.send.selectemails` | `email` |
| Redirect после подтверждения | `jw.mautic.form.action.redirect_url` | `post_url` |
| Добавить теги после успеха | `jw.mautic.lead.tags.add_campaign_doi_success_tags` | `add_campaign_doi_success_tags` |
| Удалить теги после успеха | `jw.remove_tags_doi_success_tags` | `remove_tags_doi_success_tags` |
| Добавить сегменты после успеха | `jw.add_campaign_doi_success_lists` | `add_campaign_doi_success_lists` |
| Удалить сегменты после успеха | `jw.remove_campaign_doi_success_lists` | `remove_campaign_doi_success_lists` |
| Обновить поля после успеха | `jw.mautic.form.action.lead_field_update` | `lead_field_update` |
| Обновить поля до отправки DOI email | `jw.mautic.form.action.lead_field_update_before` | `lead_field_update_before` |
| Альтернативное поле email | `jw.mautic.form.action.alternative_email_field` | `alternative_email_field` |

## Tokens

`{doi_url}` обязателен. Он заменяется на absolute URL маршрута `/doi/{enc}`,
где `enc` содержит зашифрованный payload: contact id, redirect URL, hash,
form id и настроенные success actions.

`{doi_nothuman}` опционален. Он заменяется на absolute URL маршрута
`/nothuman/{hash}`. Этот URL нужен только для anti-scanner сценария: поместите
его в скрытую ссылку, пиксель/невидимый блок или другой элемент, по которому
обычный человек не должен кликать. Если security scanner откроет этот URL до
success processing, плагин создаст marker `doi_<hash>.log`, а handler отменит
подтверждение.

Не используйте `{doi_nothuman}` как видимую кнопку или ссылку подтверждения.
Видимая ссылка подтверждения всегда должна вести через `{doi_url}`.

## Confirmation flow

Public route `/doi/{enc}`:

1. Проверяет, что integration active.
2. Декодирует URL-safe base64.
3. Расшифровывает payload через Mautic `EncryptionHelper`.
4. Проверяет contact id и загружает contact.
5. Сохраняет request context: query, request body, cookies, URI и server headers.
6. Отправляет `DoiConfirmationMessage` в `messenger.default_bus` с задержкой
   60 секунд.
7. Если dispatch недоступен, применяет fallback processing сразу.
8. Redirect выполняется на `post_url`.

Public route `/nothuman/{hash}`:

1. Проверяет, что integration active.
2. Создает marker file `doi_<hash>.log` в Mautic cache path.
3. Возвращает template `@DOIConfirm/Doi/nothuman.html.twig`.

## Success actions

Handler `DoiConfirmationMessageHandler` перед применением действий проверяет
bot-trap marker. Если marker существует, он удаляется, а success actions не
выполняются.

Если marker отсутствует, `DoiActionHelper` выполняет:

1. Audit log с `bundle=lead`, `object=doi`, `action=confirm_doi`.
2. Добавление и удаление tags.
3. Добавление и удаление segments.
4. Обновление contact fields по строке вида `alias=value`.
5. Подстановку tokens `{doi_ip}`, `{doi_timestamp}`, `{tokenid}` в field update.
6. Снятие email DNC через `EmailModel::removeDoNotContact()`.
7. Contact identification event `LeadEvents::ON_CLICKTHROUGH_IDENTIFICATION`.
8. Page hit через `PageModel::hitPage()`.
9. Webhook event `doi.successful`.

В версии `2.0.3` исходный confirmation-click request временно возвращается в
Mautic `RequestStack` на время success actions. Это важно для Mautic 7.1.3:
`IpLookupHelper` и `PageModel` читают текущий request stack для IP, privacy
headers, bot detection и trackability checks. Без этого delayed CLI processing
мог уходить в fallback context `127.0.0.1`.

## Webhooks and report

Плагин добавляет webhook events:

| Event | Когда отправляется |
| --- | --- |
| `doi.started` | После запуска DOI process и отправки DOI email |
| `doi.successful` | После успешного подтверждения и применения success actions |

Report context `jw.doi` читает Mautic `audit_log` и показывает DOI audit rows.
Report и webhook builder также зависят от integration `Active`; при выключенной
integration они не регистрируются.

## Messenger delay

Mautic 5+ использует Symfony Messenger. По умолчанию transport часто настроен
как `sync://`, поэтому DOI success actions выполняются в том же web request.
Для защиты от link scanners настройте async transport для message class:

```text
MauticPlugin\DOIConfirmBundle\Message\DoiConfirmationMessage
```

После настройки async transport убедитесь, что worker запущен штатным способом
для конкретного Mautic instance. Это operational work и выполняется владельцем
instance, не plugin source task.

## Диагностика

Быстрые проверки без изменения Mautic core:

1. Убедиться, что plugin version в Mautic registry равен `2.0.3`.
2. Убедиться, что integration `Doi Report` active.
3. Проверить, что form action сохранен с ключом `jw.email.send.lead`.
4. Проверить, что выбранный email published и содержит `{doi_url}`.
5. Если используется scanner protection, проверить наличие `{doi_nothuman}` в
   скрытой/нечеловеческой ссылке, а не в видимой кнопке.
6. Проверить, что redirect URL в `post_url` абсолютный и валидный.
7. Проверить Mautic logs на ошибки dispatch или handler.
8. Проверить `audit_log` для `bundle=lead`, `object=doi`,
   `action=confirm_doi`.
9. Проверить webhook queue/events для `doi.started` и `doi.successful`.
10. Проверить cache path на одиночные test markers `doi_<hash>.log`.

Safe cleanup для test DOI:

```sh
find var/cache -name 'doi_*.log' -type f -print
```

Удалять следует только конкретные test marker files, например:

```sh
rm var/cache/prod/doi_<hash>.log
```

Не удаляйте весь cache directory только ради DOI marker cleanup. Полный Mautic
cache rebuild — отдельная operational operation.

## Rollback

Rollback live instance выполняет SalesSnap-Operation штатным MCC/MCD путем.
Plugin source contract для rollback:

1. Предыдущий опубликованный tag: `v2.0.2`.
2. Текущий опубликованный tag: `v2.0.3`.
3. Bundle directory: `plugins/DOIConfirmBundle`.
4. Runtime state хранится в Mautic form action config, integration settings,
   contacts, DNC, audit log и webhook queue; plugin rollback не должен purge
   native settings без отдельного решения владельца instance.
5. После rollback нужно refresh/reload plugins и clear/warm cache штатным
   способом владельца instance.
6. Если async Messenger был настроен для `DoiConfirmationMessage`, worker и
   queue state проверяются отдельно владельцем instance.

## Проверка v2.0.3 для Mautic 7.1.3

Проверка source выполнена на tag `v2.0.3`:

- public routes остались `/doi/{enc}` и `/nothuman/{hash}`;
- tokens остались `{doi_url}` и `{doi_nothuman}`;
- form action context остался `jw.email.send.lead`;
- success actions, DNC removal, audit log and webhooks сохранили прежние
  storage keys and event names;
- Mautic 7.1.3 source API проверен для `RequestStack` usage в
  `IpLookupHelper`, `PageModel::hitPage()`, `EmailModel::sendEmail()` и
  `EmailModel::removeDoNotContact()`;
- expanded runtime service/form check был выполнен на локальном Mautic 7.x
  install и инстанцировал controller, listeners, handler, helpers, integration
  и form type.

Ограничение evidence: live delivery, live webhook receiver, live queue worker и
customer-host installation не выполнялись в plugin source task.
