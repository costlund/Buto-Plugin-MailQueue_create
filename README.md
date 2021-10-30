# Buto-Plugin-MailQueue_create
Create mail in table mailqueue_queue.

## Example
Cron url /mail_alert/create.

## Settings
Create email for each user once per day.
```
plugin_modules:
  mail_alert:
    plugin: mail/queue_create
    settings:
      mysql: 'yml:/../buto_data/theme/[theme]/mysql.yml'
      tag: 'errors_[date]'
      sql:
        sum: select count(id) as sum from errorlog_log where left(created_at,10)=left(date_add(now(), interval -1 day),10) and HTTP_HOST<>'localhost'
        users: "select id, email from account where id not in (select account_id from mailqueue_queue where tag='[tag]')"
      mail:
        subject: Error log
        declarment: Daily report on how many errors yesterday.
        message: 'There was [sum] errors yesterday (not localhost).'
```
