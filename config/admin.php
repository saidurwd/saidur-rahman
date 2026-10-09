<?php

/*
 * Admin sidebar. An item with a `route` that is not registered yet renders
 * disabled, so later phases only need to add the route.
 */
return [
    'menu' => [
        ['label' => 'Dashboard', 'icon' => 'speedometer2', 'route' => 'admin.dashboard'],

        ['header' => 'Content'],
        ['label' => 'Projects', 'icon' => 'kanban', 'route' => 'admin.projects.index'],
        ['label' => 'Articles', 'icon' => 'journal-text', 'route' => 'admin.posts.index'],
        ['label' => 'Experience', 'icon' => 'briefcase', 'route' => 'admin.experiences.index'],
        ['label' => 'Education', 'icon' => 'mortarboard', 'route' => 'admin.educations.index'],
        ['label' => 'Certifications', 'icon' => 'patch-check', 'route' => 'admin.certifications.index'],
        ['label' => 'Skills', 'icon' => 'bar-chart-steps', 'route' => 'admin.skills.index'],
        ['label' => 'Services', 'icon' => 'tools', 'route' => 'admin.services.index'],
        ['label' => 'Testimonials', 'icon' => 'chat-quote', 'route' => 'admin.testimonials.index'],
        ['label' => 'Pages', 'icon' => 'file-earmark-text', 'route' => 'admin.pages.index'],

        ['header' => 'Inbox'],
        ['label' => 'Messages', 'icon' => 'envelope', 'route' => 'admin.messages.index', 'badge' => 'unread_messages'],

        ['header' => 'Files'],
        ['label' => 'CV files', 'icon' => 'file-earmark-person', 'route' => 'admin.cv-files.index'],
        ['label' => 'Media', 'icon' => 'images', 'route' => 'admin.media.index'],

        ['header' => 'Site'],
        ['label' => 'Profile & settings', 'icon' => 'gear', 'route' => 'admin.settings.edit'],
        ['label' => 'Menus', 'icon' => 'list-nested', 'route' => 'admin.menus.index'],
        ['label' => 'Redirects', 'icon' => 'signpost-split', 'route' => 'admin.redirects.index'],

        ['header' => 'System'],
        ['label' => 'Activity log', 'icon' => 'clock-history', 'route' => 'admin.activity.index'],
        ['label' => 'Cache clear', 'icon' => 'arrow-repeat', 'route' => 'admin.cache.clear', 'method' => 'post'],

        ['header' => 'Account'],
        ['label' => 'Security (2FA)', 'icon' => 'shield-lock', 'route' => 'admin.two-factor.setup'],
        ['label' => 'Password', 'icon' => 'key', 'route' => 'admin.password.edit'],
    ],
];
