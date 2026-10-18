# Demra Ideal College — Dynamic CMS implementation plan

## Decision proposed (pending hosting confirmation)
WordPress CMS with a bespoke Bangla-first theme based on the existing frontend. Do not deploy a separate, insecure homegrown authentication system.

## Shared layout
- WordPress theme header.php and footer.php are single sources of truth for every page.
- Site Identity supports an admin-uploaded college logo.
- Navigation menus editable in admin, mobile accessible.
- Global design tokens for college brand colors.

## Admin-managed modules
- Slides: image, title, subtitle, CTA, ordering, active state
- Teachers: verified name, photo, subject, designation, department, display order
- Notices: title, body, PDF, category, published date, expiry, pinned state
- Admissions: instructions, downloadable forms, verified dates
- Academics: routine, syllabus, exams, results links
- News, events, gallery, pages
- Footer contacts and social links
- Roles: administrator, editor, author with least privilege
- SEO metadata, media, revision history

## Implementation stages
1. Confirm cPanel host, PHP version, MySQL/MariaDB, SSL, backup and staging subdomain.
2. Build custom WordPress theme and test on staging. Maintain current static preview until approved.
3. Implement teacher, notice, slide and other content types using a version-controlled plugin, not theme-only registrations.
4. Configure admin roles, media uploads, validation, escaping, nonces, and CSRF protections; enforce MFA and HTTPS.
5. Import **only approved and verified** college data; never migrate the compromised old website.
6. Set up cPanel deployment from GitHub (Git Version Control if supported, otherwise vetted deployment method) for theme/plugin code. CMS content remains in database/media and must not be overwritten by deployments.
7. Verify backups and rollback; only then switch production domain.

## Safety
- Existing website reported compromised: treat its database, themes, plugins, and uploaded files as untrusted.
- Do not store hosting credentials, secrets, or wp-config.php in GitHub.
- No live-domain changes until user authorizes and backup/staging are verified.
- Never publish placeholder teachers or illustrative images as official content.

## Next required details
Hosting provider and cPanel features (PHP, MySQL, Git Version Control, SSH), whether the domain already points to this hosting, and staging availability.
