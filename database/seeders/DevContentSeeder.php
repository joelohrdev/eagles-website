<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Camp;
use App\Models\CampRegistration;
use App\Models\Coach;
use App\Models\ContactSubmission;
use App\Models\FacilityPhoto;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Team;
use App\Models\Tryout;
use App\Models\TryoutRegistration;
use App\Models\User;
use App\Services\ImageUploader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Placeholder content for local development. Never run in production.
 */
class DevContentSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'staff@example.com'],
            ['name' => 'Staff Member', 'password' => 'password', 'role' => UserRole::Staff, 'email_verified_at' => now()],
        );

        $coaches = collect([
            ['name' => 'Mike Reynolds', 'title' => 'Director of Baseball Operations'],
            ['name' => 'Chris Alvarez', 'title' => 'Head Coach'],
            ['name' => 'Danny Kowalski', 'title' => 'Pitching Coach'],
            ['name' => 'Tony Marchetti', 'title' => 'Hitting Coach'],
        ])->map(fn (array $coach, int $index) => Coach::factory()->create([...$coach, 'sort_order' => $index]));

        foreach (['9U', '10U', '11U', '12U', '13U', '14U'] as $index => $division) {
            Team::factory()->create([
                'name' => "Eagles {$division} Navy",
                'division' => $division,
                'coach_id' => $coaches[$index % $coaches->count()]->id,
                'sort_order' => $index,
            ]);
        }

        $openTryout = Tryout::factory()->create(['title' => '12U Tryouts', 'division' => '12U', 'capacity' => 30]);
        Tryout::factory()->registrationUpcoming()->create(['title' => '14U Tryouts', 'division' => '14U']);
        Tryout::factory()->registrationClosed()->create(['title' => '10U Tryouts', 'division' => '10U']);
        TryoutRegistration::factory()->count(6)->create(['tryout_id' => $openTryout->id]);

        $freeCamp = Camp::factory()->free()->create(['name' => 'Fall Fundamentals Camp', 'capacity' => 40]);
        $paidCamp = Camp::factory()->paid(12500)->create(['name' => 'Winter Hitting Camp', 'capacity' => 24]);
        Camp::factory()->past()->create(['name' => 'Summer Skills Camp']);
        CampRegistration::factory()->count(5)->create(['camp_id' => $freeCamp->id]);

        foreach (CampRegistration::factory()->count(3)->make(['camp_id' => $paidCamp->id]) as $registration) {
            $order = Order::factory()->camp()->paid()->create([
                'name' => $registration->parent_name,
                'email' => $registration->email,
                'subtotal' => $paidCamp->price,
                'total' => $paidCamp->price,
            ]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_variant_id' => null,
                'description' => "{$paidCamp->name} — {$registration->player_first_name} {$registration->player_last_name}",
                'size' => null,
                'color' => null,
                'unit_price' => $paidCamp->price,
            ]);
            $registration->order_id = $order->id;
            $registration->save();
        }

        $products = collect([
            ['name' => 'Eagles Dri-Fit Tee', 'price' => 2500],
            ['name' => 'Eagles Hoodie', 'price' => 4500],
            ['name' => 'Eagles Snapback', 'price' => 3000],
            ['name' => 'Eagles Practice Jersey', 'price' => 3500],
        ])->map(fn (array $product, int $index) => Product::factory()->create([...$product, 'sort_order' => $index]));

        foreach ($products as $product) {
            foreach (['YM', 'YL', 'S', 'M', 'L', 'XL'] as $size) {
                ProductVariant::factory()->create(['product_id' => $product->id, 'size' => $size, 'color' => 'Navy', 'stock' => 25]);
            }
        }

        foreach (range(1, 3) as $i) {
            $variant = ProductVariant::query()->inRandomOrder()->first();
            $order = Order::factory()->paid()->create(['subtotal' => $variant->price(), 'total' => $variant->price()]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_variant_id' => $variant->id,
                'description' => $variant->product->name,
                'size' => $variant->size,
                'color' => $variant->color,
                'unit_price' => $variant->price(),
            ]);
        }

        foreach (range(0, 5) as $index) {
            FacilityPhoto::factory()->create([
                'image_path' => $this->placeholderImage('facility', $index),
                'caption' => ['Indoor batting cages', 'Pitching mounds', 'Turf infield', 'Weight room', 'Team meeting space', 'Outdoor field'][$index],
                'sort_order' => $index,
            ]);
        }

        ContactSubmission::factory()->count(5)->create();
    }

    /**
     * Write a solid-color placeholder image (full + thumb) and return the stored path.
     */
    private function placeholderImage(string $directory, int $seed): string
    {
        $colors = [[0x16, 0x1A, 0x35], [0x6D, 0x96, 0xB6], [0xB7, 0xB6, 0xB4], [0x23, 0x28, 0x47], [0x55, 0x7D, 0x9C], [0x1B, 0x20, 0x40]];
        [$r, $g, $b] = $colors[$seed % count($colors)];

        $canvas = imagecreatetruecolor(1200, 800);

        if ($canvas === false) {
            throw new RuntimeException('Unable to create the placeholder canvas.');
        }

        $color = imagecolorallocate($canvas, $r, $g, $b);

        if ($color === false) {
            throw new RuntimeException('Unable to allocate the placeholder colour.');
        }

        imagefill($canvas, 0, 0, $color);
        ob_start();
        imagepng($canvas);
        $png = (string) ob_get_clean();
        imagedestroy($canvas);

        $name = Str::random(40).'.webp';

        Image::fromBytes($png)->scale(width: ImageUploader::MAX_WIDTH)->toWebp()->storePubliclyAs($directory, $name, ImageUploader::DISK);
        Image::fromBytes($png)->cover(ImageUploader::THUMB_WIDTH, ImageUploader::THUMB_HEIGHT)->toWebp()->storePubliclyAs("{$directory}/thumbs", $name, ImageUploader::DISK);

        return "{$directory}/{$name}";
    }
}
