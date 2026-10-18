-- DIC CMS foundation. Run in the Supabase SQL editor after reviewing.
create extension if not exists pgcrypto;
create table if not exists public.admin_users (
 user_id uuid primary key references auth.users(id) on delete cascade,
 role text not null check(role in ('super_admin','editor')),
 created_at timestamptz not null default now()
);
create or replace function public.is_cms_admin() returns boolean language sql stable security definer set search_path = '' as $$
 select exists(select 1 from public.admin_users where user_id = (select auth.uid()))
$$;
create or replace function public.is_cms_super_admin() returns boolean language sql stable security definer set search_path = '' as $$
 select exists(select 1 from public.admin_users where user_id = (select auth.uid()) and role='super_admin')
$$;
create table if not exists public.site_settings (
 key text primary key,
 value jsonb not null default '{}'::jsonb,
 updated_at timestamptz not null default now()
);
create table if not exists public.cms_content (
 id uuid primary key default gen_random_uuid(),
 type text not null check(type in ('teacher','notice','slide','page','news','event','gallery','admission','academic')),
 title text not null,
 slug text,
 body text,
 image_url text,
 subject text,
 designation text,
 department text,
 attachment_url text,
 meta jsonb not null default '{}'::jsonb,
 sort_order int not null default 0,
 published boolean not null default false,
 published_at timestamptz,
 created_at timestamptz not null default now(),
 updated_at timestamptz not null default now(),
 unique(type,slug)
);
create index if not exists cms_content_public_idx on public.cms_content(type,published,sort_order);
alter table public.admin_users enable row level security;
alter table public.site_settings enable row level security;
alter table public.cms_content enable row level security;
drop policy if exists admin_users_read on public.admin_users;
create policy admin_users_read on public.admin_users for select to authenticated using (user_id = (select auth.uid()) or public.is_cms_super_admin());
drop policy if exists admin_users_manage on public.admin_users;
create policy admin_users_manage on public.admin_users for all to authenticated using (public.is_cms_super_admin()) with check (public.is_cms_super_admin());
drop policy if exists settings_public_read on public.site_settings;
create policy settings_public_read on public.site_settings for select to anon,authenticated using (true);
drop policy if exists settings_admin_write on public.site_settings;
create policy settings_admin_write on public.site_settings for all to authenticated using (public.is_cms_admin()) with check (public.is_cms_admin());
drop policy if exists content_public_read on public.cms_content;
create policy content_public_read on public.cms_content for select to anon,authenticated using (published = true or public.is_cms_admin());
drop policy if exists content_admin_write on public.cms_content;
create policy content_admin_write on public.cms_content for all to authenticated using (public.is_cms_admin()) with check (public.is_cms_admin());
-- Bootstrap first super_admin securely in SQL editor after creating an Auth user:
-- insert into public.admin_users(user_id,role) values ('AUTH-USER-UUID','super_admin');
-- Never embed the service_role key in frontend JavaScript.
