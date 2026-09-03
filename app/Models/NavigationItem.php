<?php

namespace App\Models;

use App\Support\Seo\StaticPages;
use Database\Factories\NavigationItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

/**
 * @property int $id
 * @property string $location
 * @property string $label
 * @property string|null $route_name
 * @property string|null $url
 * @property bool $opens_in_new_tab
 * @property bool $is_visible
 * @property int $sort_order
 */
#[Fillable(['location', 'label', 'route_name', 'url', 'opens_in_new_tab', 'is_visible', 'sort_order'])]
class NavigationItem extends Model
{
    /** @use HasFactory<NavigationItemFactory> */
    use HasFactory;

    public const string HEADER = 'header';

    public const string FOOTER = 'footer';

    public const string FOOTER_BOTTOM = 'footer_bottom';

    /** @var list<string> */
    public const array LOCATIONS = [self::HEADER, self::FOOTER, self::FOOTER_BOTTOM];

    protected $attributes = [
        'opens_in_new_tab' => false,
        'is_visible' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'opens_in_new_tab' => 'boolean',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Resolved href: the named route's path (relative, so it is safe to cache across hosts)
     * for page links, otherwise the custom URL.
     */
    public function href(): string
    {
        if ($this->route_name && Route::has($this->route_name)) {
            return route($this->route_name, absolute: false);
        }

        return $this->url ?? '#';
    }

    public function isPageLink(): bool
    {
        return $this->route_name !== null;
    }

    public function pageLabel(): ?string
    {
        return $this->route_name ? (StaticPages::all()[$this->route_name]['label'] ?? $this->route_name) : null;
    }

    #[Scope]
    protected function visible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    #[Scope]
    protected function location(Builder $query, string $location): Builder
    {
        return $query->where('location', $location);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
