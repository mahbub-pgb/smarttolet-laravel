<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('role', 'super_admin')->first() ?? User::factory()->create();

        $category = BlogCategory::firstOrCreate(
            ['slug' => 'renting-tips'],
            ['name' => 'Renting Tips', 'description' => 'Advice for tenants and landlords in Bangladesh.'],
        );

        $tags = collect(['dhaka', 'apartments', 'budget'])
            ->map(fn ($name) => BlogTag::firstOrCreate(['slug' => $name], ['name' => ucfirst($name)]));

        $posts = [
            ['title' => 'How to find an affordable flat in Dhaka', 'excerpt' => 'A practical guide to budget hunting.'],
            ['title' => 'Tenant rights every renter should know', 'excerpt' => 'Know your rights before you sign.'],
            ['title' => 'Top 5 family-friendly neighbourhoods', 'excerpt' => 'Where families love to live.'],
        ];

        foreach ($posts as $data) {
            $post = BlogPost::firstOrCreate(
                ['title' => $data['title']],
                [
                    'author_id' => $author->id,
                    'category_id' => $category->id,
                    'excerpt' => $data['excerpt'],
                    'body' => fake()->paragraphs(6, true),
                    'status' => BlogPost::STATUS_PUBLISHED,
                    'published_at' => now(),
                ],
            );

            $post->tags()->syncWithoutDetaching($tags->pluck('id')->all());
        }
    }
}
