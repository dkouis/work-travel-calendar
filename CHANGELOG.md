# Changelog

All notable changes to the Work & Travel Calendar. Dates are Athens time.
"Upload" says which server files changed; the `data` folder (calendar, accounts, passwords) is never replaced.

## [2.8.1] – 2026-11-06
Upload: `index.php`

### Fixed
- The sign-in page always showed the "Choose a new password" fields, although no reset had been requested. They were only displayed by mistake: signing in with username and password worked, and anything typed in them was ignored, so no password was changed. They now appear only when a reset asks for a new password.
- More generally, nothing marked as hidden can be displayed by a style any more.

## [2.8.0] – 2026-11-05 — Add menu and View menu
Upload: `index.php`

### Changed
- The quick-add text box and the "＋ New" button are combined into one **＋ Add ▾** menu:
  - **Quick add** field at the top (type and press Enter; the preview shows inside the menu).
  - **Event** by type: Meeting, Appointment, Deadline (all-day), Travel, Social, Other; the editor opens with that type chosen.
  - **Task**, **Project**, **Flight** (new small window: date, from, to, Morning/Noon/Night, flight number), **Work status, location or comment** (opens the day).
  - **From a template**, **Plan my day**, **Import calendar file (.ics)**, **Import tasks (CSV)**.
  - Keyboard: **Q** opens it with the quick-add field ready, **N** opens it on the first option, **Esc** closes it.
- The Day / 3 days / Week / Month / Agenda / Board buttons are now one **View ▾** menu showing the current view's name; the shortcut keys are listed in it.
- The space saved lets the full date title fit (for example "Monday, 2 November 2026" or "Week 45 · 2–8 Nov 2026"), also on phones.

### Fixed
- New events from the menu use the active time zone (they used the day's location).

## [2.7.0] – 2026-11-05 — Legend in a dropdown
Upload: `index.php`

### Changed
- The **legend** is no longer a row above the calendar. It is a **ℹ Legend ▾** dropdown in the toolbar of the Day, 3 days, Week, Month and Agenda views, so the calendar starts higher on the screen.
- The dropdown groups the items: Work status (Workplace, Telework, Telework abroad, Leave, Sick, Public holiday), Day colours (Athens + Telework, Athens + Telework abroad, half-day leave shading), Location & travel (location, location from the last flight, flight) and Events & tasks (event, task block, task due, task overdue, conflict, repeating, reminders, private, busy). It closes when you click elsewhere.
- The legend is also available on phones (it was hidden there before).

### Removed
- The "Legend" tick box in the **Show ▾** menu (no longer needed).

## [2.6.0] – 2026-11-05 — Edit in a new tab
Upload: `index.php`

### Added
- The **edit window** of every item type (events of all types, tasks, projects) opens as a **full page in a new browser tab**, for more room: wider fields and a taller Markdown notes editor; Save and Cancel stay visible at the bottom.
  - Opens from everywhere an editor opened before: the tasks panel, the board, a details tab's **Edit**, the popup's **Edit**, ＋ New (event, task, project), the grid's "More options…", quick add with Ctrl+Enter, search results and project pages.
  - New items are handed to the tab through the browser's storage, which is cleared as soon as the tab reads them.
  - Editing the same item again brings its edit tab to the front.
- **Save** waits until the change has reached the server, then closes the tab. The calendar and details tabs update by themselves.
- **Cancel** with unsaved changes asks "Discard changes?"; Escape never closes the edit page; the browser warns before closing the tab with unsaved changes.
- **Delete** from the edit tab closes it after deleting; cancelling the delete returns to the editor.
- If saving fails (no connection, or someone else saved at the same moment), the tab stays open and the message at the top explains what to do.
- Setting **⚙ Settings → General → Edit windows**: "In a new browser tab" (default) or "In a popup on the calendar" (previous behaviour).
- Viewers get a read-only page in the tab.

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

[2.8.1]: https://github.com/dkouis/work-travel-calendar/commits/main
[2.8.0]: https://github.com/dkouis/work-travel-calendar/commits/main
[2.7.0]: https://github.com/dkouis/work-travel-calendar/commits/main
[2.6.0]: https://github.com/dkouis/work-travel-calendar/commits/main
[2.5.0]: https://github.com/dkouis/work-travel-calendar/commits/main
