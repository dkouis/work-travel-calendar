# Work & Travel Calendar

A personal planner for work status, location and travel, extended with projects, events, tasks, reminders and reports.
It runs on any PHP 7.4+ web host: `index.php` is the app, `api.php` handles sign-in and stores the calendar in `site/data/`.

See [CHANGELOG.md](CHANGELOG.md) for what changed in each version.

## Features
- Daily work status (morning 08:30–13:00 / evening 13:00–17:30), location and flights (Morning 08–12, Noon 16–20, Night 20–24, with alarms)
- Work and Personal calendars, projects, events, tasks scheduled as time blocks
- Day, 3-day, Week, Month, Agenda and Board views; drag and drop; option to hide weekends
- Details of events, projects and flights, and the edit windows of events, tasks and projects, open in their own browser tab (or a popup, per setting)
- Active time zone (🌍 menu): items entered and shown in local time while travelling
- Reminders, popups, desktop notifications, daily agenda and weekly review
- Reports: daily, end of day, weekly, project, time allocation, trips (print, CSV, .ics)
- Notes in Markdown (CommonMark) with a formatting toolbar and preview
- Quick add in English and Greek, templates, search, keyboard shortcuts, undo
- Editor and viewer accounts (hashed passwords in `site/data/users.php`); change own password; reset by placing `data/reset-passwords.txt` on the server; Personal items encrypted in the browser with a passphrase
- .ics import/export (Outlook, Google), CSV task import, backups and server version history

## Project layout and development

```
FOLDERS
  site/   What goes on the web server, ready to upload:
            index.php   the app (built from src/ — do not edit by hand)
            api.php     sign-in, loading and saving (edit directly)
            sw.js, manifest.webmanifest, icon*.png, icon.svg  installable app
            .htaccess   opens index.php for the folder address
            data/       empty folder with its protection files. On the server
                        this holds your live calendar and accounts: never
                        upload this folder over the one on the server.
            HOW TO UPDATE.txt  install and update steps
  src/    Source of index.php:
            head.php    PHP headers and <head>
            base.css    original styles, app.css  styles for the planner
            body.html   page layout
            js/00-core.js … 99-boot.js  the app, in load order:
              00 core (constants, dates, time zones, state)
              01 data model, migration, Personal encryption, recurrence
              02 server: load, auto-save, merge, sign-in
              03 shell: side panel, tasks panel, dialogs, popups
              04 views: Day, 3 days, Week, Month, Agenda, Board
              05 time-grid drag/drop and keyboard
              06 items: popover, day panel, bulk actions
              07 editors: event, task, project, project page
              06b Markdown notes: renderer and toolbar editor
              08 planning: free slots, auto-schedule, plan my day, timer, focus
              09 reminders and notifications
              10 reports
              11 .ics/CSV import-export, backup, archive, templates,
                 privacy, settings, accounts, history
              12 quick add (English and Greek)
              13 search, shortcuts, top bar, time-zone menu
              14 details of events, projects and flights, and edit windows, in their own tab
  build.py  Joins src/ into site/index.php (needs Python 3 and Node.js for
            the syntax check):   python3 build.py
  tests/    Browser tests (Python 3 + Playwright + PHP):
              1. copy site/ to a test folder and run: php -S 127.0.0.1:8099 -t <folder>
              2. python3 tests/seed.py <folder>      (creates a sample old-format calendar;
                 run it again before each test suite: func.py adds 5,000 items for its speed test)
              3. create accounts dkouis / secret123 and viewer / viewer123 on first open
              4. WTC_SRV=<folder> python3 tests/func.py        (108 checks)
              5. WTC_SRV=<folder> python3 tests/passwords.py   (20 checks: reset file, forced change, change password)
              6. WTC_SRV=<folder> python3 tests/detail.py      (20 checks: details in a new tab)
              7. WTC_SRV=<folder> python3 tests/edit.py        (28 checks: edit windows in a new tab)

TO CHANGE THE APP
  Edit files in src/, run python3 build.py, then upload site/index.php.
```

## Third-party code

`src/vendor/markdown-it.min.js` — [markdown-it](https://github.com/markdown-it/markdown-it) 14.1.0, MIT licence (`src/vendor/markdown-it.LICENSE`). `build.py` includes it in `site/index.php`.
