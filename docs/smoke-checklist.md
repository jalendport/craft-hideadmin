# Hide Admin — manual smoke checklist

The plugin's enforcement is inseparable from Craft's user elements, controllers, and element index, so it's verified by hand against a real install rather than in CI. The pure decision logic (`HiddenUsers::hides()` and `HiddenUsers::filterSources()`) is the only part suitable for unit tests; everything below exercises the wired-up behavior.

Run this against Craft 4.3.0+ before tagging a release.

## Setup

- One **admin** user (call them Ada).
- One **non-admin** user (call them Nate) in a user group with the "Edit users" permission (and "Register users"/"Delete users" if you want to exercise those paths).
- A section entry type with a **Users field** whose sources are limited to one or more specific groups (not "All").
- An entry whose author is the admin, Ada (to observe the author side effect below).

Log in as Nate (non-admin) for every check unless it says otherwise.

## Checks

### Bug fixes
- [ ] **#10 — no crash without a session.** Make a GraphQL request that resolves users (e.g. a `users` query). It returns without a `Trying to get property 'admin' of non-object` error. Then run `php craft resave/users` on the CLI — it completes without error.
- [ ] **#9 — limited-source Users fields work.** Edit the entry with the source-limited Users field. As Nate, you can open the field's element selector and **select a user**. (Before the fix, the modal showed no selectable users unless the field was set to "All".)

### Index filtering
- [ ] **Admins source hidden.** On the Users index, Nate sees no "Admins" source in the sidebar, and admin users (Ada) do not appear in any source's results.
- [ ] **"All Users" still present.** The "All Users" source is still there and lists non-admins.
- [ ] **No "Admin" filter.** Open the Users index filter/condition builder as Nate — there is no "Admin" rule to add.
- [ ] **Admin is unaffected.** Log in as Ada: the Admins source, admin users, and the Admin filter rule are all present as normal.

### Access control (the headline fix)
- [ ] **Can't view an admin directly.** As Nate, visit the admin's edit screen by ID (`/admin/users/<Ada's id>`). You get a "not found" (404) or forbidden (403) response, not the edit form. (Craft 4's users controller loads the target by query and only checks the general "Edit users" permission, so the query filter is what turns this into a 404.)
- [ ] **Can't save an admin.** Attempt to POST a save to Ada's user (e.g. a crafted `users/save-user` request with Ada's `userId`) — it's rejected.
- [ ] **Can't delete an admin.** Attempt to delete Ada — denied. (Craft core already blocks this; this confirms the plugin doesn't regress it.)
- [ ] **Not selectable in relation fields.** As Nate, open a Users field's element selector — admin users don't appear and can't be selected. Non-admin users still can.
- [ ] **Not enumerable via crafted index requests.** A hand-crafted request to the element-index endpoint (e.g. toggling an `admin` filter) returns no admin users.
- [ ] **Self still works.** As Nate, open and save your **own** account (`/admin/myaccount` / your user edit screen) — it works normally.
- [ ] **Non-admins still visible.** As Nate, open and edit another **non-admin** user — it works normally.

## Scope and side effects

- [ ] **Admin authors read as blank.** As Nate, view an entry authored by Ada (an admin). The author reads as empty, because admin users are filtered out of control-panel user queries for non-admins. This is expected — it's the same hiding that blocks the edit screen. A non-admin who can't edit the author field won't lose the stored author on save; confirm the author is unchanged after Nate saves such an entry.
- [ ] **Front end and GraphQL are not filtered.** A logged-out (or front-end) `craft.users` query and a GraphQL `users` query still return admin users. Hiding is scoped to the control panel by design; this is verified separately by the `#10` checks above (they must not error).

Complete, **configurable** hiding — per-group visibility rules, and opt-in front-end/GraphQL enforcement — ships in **Silo**, the Craft 5 successor. See `PLAN.md`.
