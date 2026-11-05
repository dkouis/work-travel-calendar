# Changelog

All notable changes to the Work & Travel Calendar. Dates are Athens time.
"Upload" says which server files changed; the `data` folder (calendar, accounts, passwords) is never replaced.

## [2.5.0] – 2026-11-05 — Details in a new tab
Upload: `index.php`

### Added
- Clicking an **event** (meeting, appointment, deadline, travel, social, other), a **project** or a **flight** opens its details in a **new browser tab**:
  - Event: type, date and time (active time zone, plus the zone it was entered in), repeat rule, calendar, project, location, online link, attendees, reminders, tags, links, linked tasks, conflicts and the Markdown notes. Buttons: Edit, Follow-up task, Duplicate, Join, Show in calendar, .ics, Delete, Close tab.
  - Repeating events open the occurrence that was clicked.
  - Project: the full project page (Tasks, Timeline, Stats).
  - Flight: route, date, departure and arrival (local and in the active time zone), alarm, flight number, events around it, and editing of all flight fields.
- Clicking the same item again brings its existing tab to the front instead of opening another.
- Details tabs and the calendar tab stay in sync: a change saved in one appears in the others within a second or two.
- "Show in calendar" in a details tab switches the calendar tab to that day.
- A details link can be bookmarked or shared with another account; it asks to sign in first if needed. Viewers see the details without editing buttons.
- Setting **⚙ Settings → General → Open details**: "In a new browser tab" (default) or "In a popup on the calendar" (previous behaviour).
- Flights in Month, compact Week and Agenda can now be clicked.
- Version number shown in ⚙ Settings → General.

### Notes
- If the browser blocks the new tab, the app says so and shows the details in the current tab; allow pop-ups for the calendar's address.
- Only the calendar tab shows the daily agenda and fires reminders while it is open.

## [2.4.0] – 2026-11-05 — Password reset
Upload: `api.php`, `index.php`

### Added
- Reset passwords by placing `reset-passwords.txt` in the server's `data` folder (`username temporary-password [editor|viewer]`, or `*` for all accounts). The file is applied on the next visit and then deleted; results go to `data/reset-passwords-log.txt` without passwords. Unknown usernames are created (recovery when no editor can sign in).
- Accounts with a temporary password must choose a new one at sign-in; other sessions of those accounts are signed out.
- **⋯ → Change my password** for every account.
- User accounts window: **Must change** column.

### Notes
- Passwords remain in `data/users.php` as hashes only.

## [2.3.0] – 2026-11-05 — Weekends, Markdown notes
Upload: `index.php`

### Added
- **Hide weekends** option for 3 days, Week (time grid and compact) and Month.
- Event and task notes in **Markdown (CommonMark)** with a formatting toolbar, Write/Preview tabs, Ctrl+B/I/K and list continuation (markdown-it 14.1.0; raw HTML and unsafe links blocked).

### Removed
- The "2nd zone" column in the time grid; the top-bar time-zone menu is the only time-zone setting.

## [2.2.3] – 2026-11-02
Upload: `index.php`

### Fixed
- Import calendar file (.ics): "Import selected" did nothing.
- Imported events that give their length as a duration (Google, Apple) got the wrong end time.

## [2.2.2] – 2026-11-02
Upload: `api.php`

### Fixed
- Signing in with "Keep me signed in", and signing out, failed with "The server answered with an error (500)".

## [2.2.1] – 2026-11-01
### Added
- **3 days** view (key 3).
### Changed
- At most three notification popups are shown at once.

## [2.2.0] – 2026-11-01 — Time zones
### Added
- **Active time zone** menu (🌍) in the top bar: items are entered and shown in it; switching moves everything to local time; suggestion when your flights show you in another city.
### Changed
- Flight periods: Morning 08:00–12:00 (alarm 06:00), Noon 16:00–20:00 (alarm 14:00), Night 20:00–24:00 (alarm 17:30), local to the departure city; times and alarm editable per flight. Flights saved as "Evening" show as Noon.

## [2.1.0] – 2026-11-01
### Added
- Flights drawn in the Day and Week time grids, with editable departure and arrival times.
- Work day split into Morning 08:30–13:00 and Evening 13:00–17:30; half-day leave affects warnings, free-slot search and reminders only for that half.
### Changed
- Titles of events and tasks shown in red in their details.

## [2.0.0] – 2026-11-01 — Planner
Upload: `index.php` and the installable-app files

### Added
- Projects (with sub-projects, budgets, project page), Work and Personal calendars, events, tasks with time blocks on the calendar.
- Day, Week (time grid and compact), Month, Agenda and Board views; drag and drop; conflict detection.
- Reminders, popups, desktop notifications, catch-up of missed reminders, daily agenda and weekly review prompts.
- Reports: daily agenda, end of day, weekly review, project, time allocation, trips (print, CSV, .ics, copy, email).
- Planning: find a slot, auto-schedule, plan my day, timer and focus mode.
- Quick add in English and Greek, templates, search, keyboard shortcuts, undo/redo.
- Personal items encrypted in the browser with a passphrase; viewers see "Busy".
- .ics export/import, CSV task import, backups, archive, installable app, offline copy.
- Automatic upgrade of existing calendars; days, statuses, locations, flights and comments kept unchanged.

## [1.x] – before 2026-11-01
- Work status, location, flights and comments per day; Week and Month views; Trips report.
- 1 Nov 2026: moved from a single HTML file to the PHP version (`index.php` + `api.php`) with server accounts, automatic saving and version history.

[2.5.0]: https://github.com/dkouis/work-travel-calendar/releases/tag/v2.5.0
