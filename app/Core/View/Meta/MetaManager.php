<?php declare(strict_types=1);
/**
 * Meta Manager
 *
 * Manages HTML meta tags for SEO and social sharing.
 * Pure mechanism - site defaults loaded from config/meta.php
 *
 * @package App\Core\View\Meta
 */
namespace App\Core\View\Meta;

final class MetaManager
{
    /**
     * Meta tag storage
     *
     * @var array<string, string|null>
     */
    private array $tags = [];

    /**
     * Open Graph tags
     *
     * @var array<string, string|null>
     */
    private array $openGraph = [];

    /**
     * Twitter Card tags
     *
     * @var array<string, string|null>
     */
    private array $twitter = [];

    /**
     * Link tags (canonical, favicon, etc.)
     *
     * @var array<array{rel: string, href: string, type?: string}>
     */
    private array $links = [];

    /**
     * Page title
     */
    private string $title = '';

    /**
     * Title suffix (e.g., " | Site Name")
     */
    private string $titleSuffix = '';

    /**
     * Whether to append suffix to title
     */
    private bool $appendSuffix = true;

    /**
     * Charset
     */
    private string $charset = 'UTF-8';

    /**
     * Viewport
     */
    private string $viewport = 'width=device-width, initial-scale=1.0';

    /**
     * Robots directive
     */
    private ?string $robots = null;

    /**
     * Canonical URL
     */
    private ?string $canonical = null;

    public function __construct()
    {
        $this->loadDefaults();
    }

    /**
     * Load defaults from config
     */
    private function loadDefaults(): void
    {
        $config = config('meta', []);

        // Title suffix
        $this->titleSuffix = $config['title_suffix'] ?? '';

        // Charset & viewport
        $this->charset = $config['charset'] ?? 'UTF-8';
        $this->viewport = $config['viewport'] ?? 'width=device-width, initial-scale=1.0';

        // Default meta tags
        $defaults = $config['defaults'] ?? [];
        foreach ($defaults as $key => $value) {
            if (str_starts_with($key, 'og:')) {
                $this->openGraph[$key] = $value;
            } elseif (str_starts_with($key, 'twitter:')) {
                $this->twitter[$key] = $value;
            } else {
                $this->tags[$key] = $value;
            }
        }

        // Default title
        if (isset($config['default_title'])) {
            $this->title = $config['default_title'];
        }

        // Default robots
        if (isset($config['robots'])) {
            $this->robots = $config['robots'];
        }

        // Favicon
        if (isset($config['favicon'])) {
            $this->setFavicon($config['favicon']);
        }
    }

    /**
     * Set page title
     *
     * @param string $title Page title
     * @param bool $appendSuffix Whether to append site suffix
     *
     * @return static
     */
    public function setTitle(string $title, bool $appendSuffix = true): static
    {
        $this->title = $title;
        $this->appendSuffix = $appendSuffix;

        return $this;
    }

    /**
     * Get the full title with suffix
     *
     * @return string
     */
    public function getTitle(): string
    {
        if ($this->appendSuffix && $this->titleSuffix !== '') {
            return $this->title . $this->titleSuffix;
        }

        return $this->title;
    }

    /**
     * Set title suffix
     *
     * @param string $suffix Suffix (e.g., " | Site Name")
     *
     * @return static
     */
    public function setTitleSuffix(string $suffix): static
    {
        $this->titleSuffix = $suffix;

        return $this;
    }

    /**
     * Set meta description
     *
     * @param string $description Description text
     *
     * @return static
     */
    public function setDescription(string $description): static
    {
        $this->tags['description'] = $description;

        return $this;
    }

    /**
     * Set meta keywords
     *
     * @param string|array $keywords Keywords
     *
     * @return static
     */
    public function setKeywords(string|array $keywords): static
    {
        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }
        $this->tags['keywords'] = $keywords;

        return $this;
    }

    /**
     * Set author
     *
     * @param string $author Author name
     *
     * @return static
     */
    public function setAuthor(string $author): static
    {
        $this->tags['author'] = $author;

        return $this;
    }

    /**
     * Set robots directive
     *
     * @param string $robots Robots directive (e.g., 'index, follow')
     *
     * @return static
     */
    public function setRobots(string $robots): static
    {
        $this->robots = $robots;

        return $this;
    }

    /**
     * Set noindex (convenience method)
     *
     * @param bool $nofollow Also set nofollow
     *
     * @return static
     */
    public function noIndex(bool $nofollow = false): static
    {
        $this->robots = $nofollow ? 'noindex, nofollow' : 'noindex, follow';

        return $this;
    }

    /**
     * Set canonical URL
     *
     * @param string $url Canonical URL (relative or absolute)
     *
     * @return static
     */
    public function setCanonical(string $url): static
    {
        $this->canonical = $url;

        return $this;
    }

    /**
     * Set a generic meta tag
     *
     * @param string $name Meta name
     * @param string $content Meta content
     *
     * @return static
     */
    public function set(string $name, string $content): static
    {
        $this->tags[$name] = $content;

        return $this;
    }

    /**
     * Get a meta tag value
     *
     * @param string $name Meta name
     *
     * @return string|null
     */
    public function get(string $name): ?string
    {
        return $this->tags[$name] ?? null;
    }

    /**
     * Set Open Graph tag
     *
     * @param string $property OG property (with or without 'og:' prefix)
     * @param string $content Content value
     *
     * @return static
     */
    public function setOpenGraph(string $property, string $content): static
    {
        if (! str_starts_with($property, 'og:')) {
            $property = 'og:' . $property;
        }
        $this->openGraph[$property] = $content;

        return $this;
    }

    /**
     * Set multiple Open Graph tags
     *
     * @param array<string, string> $tags OG tags
     *
     * @return static
     */
    public function setOpenGraphTags(array $tags): static
    {
        foreach ($tags as $property => $content) {
            $this->setOpenGraph($property, $content);
        }

        return $this;
    }

    /**
     * Set Twitter Card tag
     *
     * @param string $name Twitter name (with or without 'twitter:' prefix)
     * @param string $content Content value
     *
     * @return static
     */
    public function setTwitterCard(string $name, string $content): static
    {
        if (! str_starts_with($name, 'twitter:')) {
            $name = 'twitter:' . $name;
        }
        $this->twitter[$name] = $content;

        return $this;
    }

    /**
     * Set multiple Twitter Card tags
     *
     * @param array<string, string> $tags Twitter tags
     *
     * @return static
     */
    public function setTwitterTags(array $tags): static
    {
        foreach ($tags as $name => $content) {
            $this->setTwitterCard($name, $content);
        }

        return $this;
    }

    /**
     * Set favicon
     *
     * @param string $path Path to favicon
     * @param string $type MIME type
     *
     * @return static
     */
    public function setFavicon(string $path, string $type = 'image/png'): static
    {
        $this->links[] = [
            'rel' => 'icon',
            'href' => $path,
            'type' => $type,
        ];
        $this->links[] = [
            'rel' => 'apple-touch-icon',
            'href' => $path,
        ];

        return $this;
    }

    /**
     * Add a link tag
     *
     * @param string $rel Relationship
     * @param string $href URL
     * @param array<string, string> $attributes Additional attributes
     *
     * @return static
     */
    public function addLink(string $rel, string $href, array $attributes = []): static
    {
        $this->links[] = array_merge(['rel' => $rel, 'href' => $href], $attributes);

        return $this;
    }

    /**
     * Render all meta tags as HTML
     *
     * @return string HTML output
     */
    public function render(): string
    {
        $output = '';

        // Charset
        $output .= '<meta charset="' . e($this->charset) . '">' . PHP_EOL;

        // Viewport
        $output .= '<meta name="viewport" content="' . e($this->viewport) . '">' . PHP_EOL;

        // Title
        $output .= '<title>' . e($this->getTitle()) . '</title>' . PHP_EOL;

        // Standard meta tags
        foreach ($this->tags as $name => $content) {
            if ($content !== null && $content !== '') {
                $output .= '<meta name="' . e($name) . '" content="' . e($content) . '">' . PHP_EOL;
            }
        }

        // Robots
        if ($this->robots !== null) {
            $output .= '<meta name="robots" content="' . e($this->robots) . '">' . PHP_EOL;
        }

        // Canonical
        if ($this->canonical !== null) {
            $output .= '<link rel="canonical" href="' . e($this->canonical) . '">' . PHP_EOL;
        }

        // Open Graph
        foreach ($this->openGraph as $property => $content) {
            if ($content !== null && $content !== '') {
                $output .= '<meta property="' . e($property) . '" content="' . e($content) . '">' . PHP_EOL;
            }
        }

        // Twitter Cards
        foreach ($this->twitter as $name => $content) {
            if ($content !== null && $content !== '') {
                $output .= '<meta name="' . e($name) . '" content="' . e($content) . '">' . PHP_EOL;
            }
        }

        // Link tags (favicon, etc.)
        foreach ($this->links as $link) {
            $attrs = '';
            foreach ($link as $attr => $value) {
                $attrs .= ' ' . e($attr) . '="' . e($value) . '"';
            }
            $output .= '<link' . $attrs . '>' . PHP_EOL;
        }

        return $output;
    }

    /**
     * Sync OG/Twitter from standard meta
     *
     * Copies title/description to OG and Twitter if not already set.
     * Call this after setting page-specific meta.
     *
     * @return static
     */
    public function sync(): static
    {
        $title = $this->getTitle();
        $description = $this->tags['description'] ?? '';

        // Sync to Open Graph
        if (! isset($this->openGraph['og:title']) || $this->openGraph['og:title'] === '') {
            $this->openGraph['og:title'] = $title;
        }
        if (! isset($this->openGraph['og:description']) || $this->openGraph['og:description'] === '') {
            $this->openGraph['og:description'] = $description;
        }

        // Sync to Twitter
        if (! isset($this->twitter['twitter:title']) || $this->twitter['twitter:title'] === '') {
            $this->twitter['twitter:title'] = $title;
        }
        if (! isset($this->twitter['twitter:description']) || $this->twitter['twitter:description'] === '') {
            $this->twitter['twitter:description'] = $description;
        }

        return $this;
    }

    /**
     * Reset to defaults (useful for testing)
     *
     * @return static
     */
    public function reset(): static
    {
        $this->tags = [];
        $this->openGraph = [];
        $this->twitter = [];
        $this->links = [];
        $this->title = '';
        $this->robots = null;
        $this->canonical = null;
        $this->appendSuffix = true;

        $this->loadDefaults();

        return $this;
    }
}
