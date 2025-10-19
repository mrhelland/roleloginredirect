# Local Plugin: Role Login Redirect

## Overview

**Role Login Redirect** is a Moodle plugin that automatically redirects users with specific roles to a designated course immediately after login.  

The plugin is designed to:
- Detect a user’s assigned roles across all contexts.
- Redirect users in chosen roles to a target course.
- Enroll these users automatically in the target course.
- Exclude redirection for administrators or any other roles you specify.

This plugin was original designed to streamlines access to a **mentor dashboard** or **parent hub** as these people often have limited experience with Moodle organization and navigation.

Current Maturity: **BETA**


## ✨ Key Features

- **Automatic redirection** after login based on configured roles.  
- **Exclusion list** to ensure privileged users (e.g. teachers, managers, admins) are never redirected.  
- **Optional auto-enrollment** into the destination course via Moodle’s *manual enrolment* plugin.  
- **Safe session handling** via `$SESSION->wantsurl` — fully compatible with Moodle’s core login flow.  
- **Admin configuration page** under *Site administration → Plugins → Local plugins → Role Login Redirect*.  
- **Fully Moodle 4.5-compliant**, using proper observer, DB, and enrollment APIs.
- **AI Translations Provided** for these languages: ar, de, en, es, fr, vi, zh_cn.


## 🧠 Possible Use Cases

- Redirect **parents/guardians** to a “Parent Dashboard” course.  
- Redirect **mentors or observers** to a central summary course.  
- Redirect **employees or clients** to a corporate landing page.  
- Simplify user navigation in multi-role Moodle environments.


## ⚙️ Installation

1. Copy or clone the plugin folder into your Moodle installation:
   `moodle/local/roleloginredirect`
2. Visit Site administration → Notifications to trigger the installation.
3. Moodle will automatically detect and install the plugin.  


## 🧾 Configuration

Go to:
`Site administration → Plugins → Local plugins → Role Login Redirect`

You can configure the following settings:

| Setting        | Description         |
|----------------|---------------------|
| Roles to redirect | Users with these roles (in any context) will be redirected after login. |
| Excluded roles	| Roles that should never be redirected (take priority over redirect list). |
| Target course ID | The numeric ID of the course to redirect users to (e.g., /course/view.php?id=5). |
| Enrollment role	| Role to assign when auto-enrolling users into the target course (defaults to student). |


## 🔒 Security Overview

- The observer never executes for site administrators.
- The plugin runs entirely within Moodle’s event observer framework.
- Uses Moodle’s core APIs for role detection, enrollment, and redirection.
- All inputs validated via PARAM_INT and intval() to prevent injection or tampering.
- Redirection handled through $SESSION->wantsurl, preserving standard login workflow.


### Event Logic 

1. On login, check plugin configuration.
2. Skip administrators and excluded roles.
3. If the user has a matching role:
    - Ensure the target course exists and is visible.
    - Auto-enroll the user if not already enrolled.
    - Set $SESSION->wantsurl to the course view page.


## 🕊️ Privacy

This plugin does not store or transmit any personal data.
It only reads user roles and sets a temporary session redirection variable.

## ⚠️ Issues

This plugin will currently break the built-in Moodle functionality to force the user to change their password on login. I attempted to fix this, but ran out of patience. 
