# Line Item Tweaks (au.com.agileware.lineitemtweaks)

This is a [CiviCRM](https://civicrm.org) extension which improves the description ("label") shown
against Line Items on Contributions, Invoices and Contribution/Event receipts. By default, CiviCRM
often leaves Line Items with a generic label (e.g. just the Financial Type name), which makes it
hard to tell, at a glance, what a Membership or Event Line Item actually relates to. This extension
automatically rewrites Line Item labels to be more descriptive, and fixes some data consistency
issues that occur when Memberships and Contributions are created out of order (for example, via
CiviCRM Webforms/Drupal Webform, where Line Items are created before the Contribution).

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## What it does

This extension has no user interface, menu items, CiviRules actions, or Scheduled Jobs — it works
entirely in the background via CiviCRM hooks (`hook_civicrm_pre` and `hook_civicrm_post`) whenever
Line Items are created or edited. Its behaviour depends on the type of Line Item:

* **Membership Line Items** — the label is rebuilt from a configurable template (see
  [Configuration](#configuration) below) including the Membership ID, Membership Type, term start
  and end dates, the Membership Organisation name, and the Member's display name.
  * For **renewals**, the extension determines the correct term dates to display:
	* If the related Contribution is *Pending*, the projected renewal dates are calculated (via
	  `CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType`).
	* Otherwise, the dates are taken from the most recent `MembershipLog` entry for the Membership.
  * For **new (non-renewal) Memberships**, the Membership's own start/end dates are used.
  * **Lifetime Memberships** use a separate label template (with no end date placeholder).
  * When a Contribution is completed (created), any of its Membership Line Items are re-checked and
	re-saved with a corrected label — this catches cases (such as Webform-based
	registration/renewal) where the Line Item was originally created before the Contribution or
	Membership renewal dates were finalised.
* **Event Participant Line Items** — the label is set to the Event title, plus the Event Type and
  Event start date, e.g. `Annual Gala (Fundraiser)  on 5/12/2026`.
* **Other (non-Membership, non-Participant) Contribution Line Items** — if the Line Item does not
  already have a custom label (i.e. it is empty or simply matches the Financial Type name), and the
  Contribution has a "Contribution Source" set, the Line Item label is set to the Contribution
  Source.

## Usage

Once installed, the extension requires no interaction — Line Item labels are automatically kept up
to date whenever Memberships, Event Participants, and Contributions are created or edited, whether
via the CiviCRM back-end, front-end Contribution/Event/Membership pages, Webforms, or the API.

## Configuration

The extension provides two Settings which control the Membership Line Item label templates:

| Setting name | Default | Description |
| --- | --- | --- |
| `lineitemtweaks_membership_label` | `Membership Id %1: %2 from %3 to %4` | Label used for ordinary (non-lifetime) Membership Line Items. |
| `lineitemtweaks_membership_label_lifetime` | `Membership Id %1: %2 from %3 onward` | Label used for Lifetime Membership Line Items. |

Both templates support the following placeholders:

* `%1` — Membership ID
* `%2` — Membership Type
* `%3` — Term/membership start date (month/year)
* `%4` — Term end date (month/year) — not applicable/unused for lifetime Memberships
* `%5` — Membership Organisation display name
* `%6` — Member's display name

This extension does not ship with a dedicated settings page in the CiviCRM Administer menu, so the
templates must be changed using the CiviCRM API, for example with
[cv](https://github.com/civicrm/cv):

```bash
cv api4 Setting.set values='{"lineitemtweaks_membership_label": "Membership #%1 (%2): %3 - %4 for %6"}'
```

### Special configuration requirements

No special configuration, credentials, or dependent extensions are required — the extension works
using the default label templates as soon as it is installed. Customising the label templates
(above) is entirely optional.

## Requirements

* CiviCRM 5.51+

## Installation (Web UI)

Learn more about installing CiviCRM extensions in the [CiviCRM Sysadmin
Guide](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/).

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this
extension and install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/agileware/au.com.agileware.lineitemtweaks.git
cv en lineitemtweaks
```

About the Authors
-----------------

This CiviCRM extension was developed by the team at [Agileware](https://agileware.com.au).

[Agileware](https://agileware.com.au) provide a range of CiviCRM services including:

  * CiviCRM migration
  * CiviCRM integration
  * CiviCRM extension development
  * CiviCRM support
  * CiviCRM hosting
  * CiviCRM remote training services

Support your Australian [CiviCRM](https://civicrm.org) developers, [contact Agileware](https://agileware.com.au/contact) today!

![Agileware](logo/agileware-logo.png)
