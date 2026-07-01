<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('role', 'super_admin')->first();

        $pages = [
            [
                'title' => 'About',
                'meta_description' => 'Learn about SmartToLet, the rental marketplace for Bangladesh.',
                'body' => '<p>SmartToLet helps people across Bangladesh find rooms, apartments, and offices to rent. '
                    .'Browse verified listings, explore them on the map, and connect directly with owners.</p>'
                    .'<p>Edit this page from the admin panel to tell your story.</p>',
            ],
            [
                'title' => 'Contact',
                'meta_description' => 'Get in touch with the SmartToLet team.',
                'body' => '<p>Have a question or need help? Reach out to us and we will get back to you.</p>'
                    .'<p>Edit this page from the admin panel to add your contact details.</p>',
            ],
            [
                'title' => 'Privacy',
                'meta_description' => 'How SmartToLet collects, uses, and protects your data.',
                'body' => '<p>This Privacy Policy explains how SmartToLet handles your personal information.</p>'
                    .'<p>Edit this page from the admin panel to publish your full policy.</p>',
            ],
        ];

        foreach ($pages as $i => $data) {
            Page::firstOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'author_id' => $author?->id,
                    'title' => $data['title'],
                    'meta_description' => $data['meta_description'],
                    'body' => $data['body'],
                    'status' => Page::STATUS_PUBLISHED,
                    'show_in_footer' => true,
                    'show_in_header' => false,
                    'sort_order' => $i,
                ],
            );
        }
    }
}
