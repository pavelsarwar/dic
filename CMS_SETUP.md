# DIC Custom CMS — setup checklist

This project is **not WordPress**. It uses static HTML/CSS/JS, Supabase Auth + PostgreSQL, and a custom admin interface.

## What is implemented
- Shared responsive header and footer on homepage and faculty page via assets/cms.js.
- Site identity, logo, navigation and contact settings read from site_settings.
- /admin/ sign-in, permission check, content CRUD for teachers, notices, slides, pages, news, events, gallery, admission and academic.
- Teacher listing reads published teacher records; unconfigured demo still shows clearly marked placeholders.
- Database schema with row-level security and administrator roles.

## Activation (not yet completed)
1. Create a **new** Supabase project for DIC. Do not reuse AskDeal's production database.
2. Run supabase/schema.sql in Supabase SQL editor.
3. In Supabase Auth, create a verified user securely; copy its UUID. Run the bootstrap INSERT documented at the bottom of schema.sql. Do not enable unrestricted public signups for CMS.
4. Set Supabase Project URL and **publishable/anon** key in assets/cms-config.js. Never commit service_role, DB passwords or admin passwords.
5. Set site_settings rows via SQL editor or later Settings UI. Example:
   insert into public.site_settings(key,value) values
   ('identity','{"name":"ডেমরা আইডিয়াল কলেজ","logo_url":""}'::jsonb),
   ('navigation','[{"label":"প্রচ্ছদ","url":"index.html"},{"label":"শিক্ষকবৃন্দ","url":"faculty.html"}]'::jsonb),
   ('contact','{"address":"ঢাকা, বাংলাদেশ"}'::jsonb)
   on conflict(key) do update set value=excluded.value;
6. Open /admin/ on a secure HTTPS staging domain and sign in. Create teacher records and publish them. Verify anonymous visitors cannot read drafts or modify content.
7. Configure approved media storage/upload workflow, file validation, and limits before production photo uploads. Current admin supports **image URLs**, not uploads.
8. Test mobile navigation, CSP, password recovery, session management, accessibility, audit logging, and rollback before production release.

## cPanel
- Confirm the hosting account has HTTPS, file manager, and (preferably) Git Version Control / SSH.
- Deploy static files to a **staging subdomain** first. For Git deployments, ensure .cpanel.yml or a reviewed deployment script points to the correct directory. Never deploy into the existing compromised webroot without isolating it.
- Supabase remains the remote database/auth backend; cPanel serves frontend assets.
- Do not move DNS or production traffic until approved, backed up, and tested.

## Outstanding
- Admin UI for site settings, media uploads, roles, notices and slide-specific fields.
- Dynamic homepage sections and public notice/slide rendering.
- Verify official logo and college content.
- Production security review and hosting integration.
