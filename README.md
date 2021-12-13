# Buto-Plugin-MailQueue_create
Create mail with just a few params automatic via cron job.
Mail data is stored in table mailqueue_queue.

## Settings
This example using url /mail_alert/create (should be used as a cron job).
```
plugin_modules:
  mail_alert:
    plugin: mail/queue_create
    settings:
      mysql: 'yml:/../buto_data/theme/[theme]/mysql.yml'
```
### Example 1
Create email for each user once per day.
Count rows in table account and send to all users not having this mail for the day.
This works well if all users should have the same message.
```
      mail:
        subject: Subject
        message: 'There are [sum] accounts just now.'
        declarment: Daily report on how many accounts we have.
      tag: 'account_count_[date]'
      sql:
        sum: select count(id) as sum from account
        users: "select id, email from account where id not in (select account_id from mailqueue_queue where tag='[tag]')"
```
### Example 2
This example shows how to check for user content. Mandatory outputs are id, email, mail_text.
```
      mail:
        subject: Login check
        message: 'Just a friendly reminder. You have not signed in since [mail_text]. Please come back.'
      tag: 'logincheck_[date]'
      sql_full: |
        select 
        a.id,
        a.email,
        (select date                  from account_log where account_id=a.id order by date desc limit 1) as mail_text,
        (select datediff(now(), date) from account_log where account_id=a.id order by date desc limit 1) as last_login_days
        from account as a
        where a.id not in (select account_id from mailqueue_queue where tag='[tag]')
        and not isnull(a.email)
        having last_login_days>7
```
Param mail/message could have elements.
```
        message:
          -
            type: p
            innerHTML: Just a friendly reminder. You have not signed in for a long time. Please come back.
          -
            type: p
            innerHTML:
              -
                type: span
                innerHTML: Last login was
              -
                type: span
                innerHTML: rs:mail_text
```
