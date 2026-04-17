<?php
namespace RRZE\Cris;
defined('ABSPATH') || exit;

use RRZE\Cris\RemoteGet;
use RRZE\Cris\XML;

/**
 * Visualization class for rendering interactive maps from CRIS API
 * 
 * Fetches visualization data from CRIS endpoint and renders as iframe
 * Data is base64+gzip encoded in API response, decoded and displayed
 */
class Visualization
{
    private string $vis_id;
    private string $page_lang;
    private string $size;
    private array $options;
    private string $content = '';
    public \WP_Error|null $error = null;

    private const SIZE_MAP = [
        's' => '400px',
        'm' => '600px',
        'l' => '900px',
    ];

    private const DEFAULT_SIZE = 'm';

    /**
     * Constructor
     * 
     * @param string $vis_id CRIS visualization ID
     * @param string $page_lang Page language ('de' or 'en')
     * @param string $size Size option ('s', 'm', or 'l')
     */
    public function __construct(string $vis_id = '', string $page_lang = 'de', string $size = self::DEFAULT_SIZE)
    {
        $this->vis_id = sanitize_text_field($vis_id);
        $this->page_lang = in_array($page_lang, ['de', 'en']) ? $page_lang : 'de';
        $this->size = isset(self::SIZE_MAP[$size]) ? $size : self::DEFAULT_SIZE;
        $this->options = (array) FAU_CRIS::get_options();

        if (empty($this->vis_id)) {
            $this->error = new \WP_Error(
                'cris-vis-id-error',
                __('Visualization ID is required', 'fau-cris')
            );
        }
    }

    /**
     * Fetch visualization data from CRIS API
     * 
     * @return bool True on success, false on error
     */
    private function fetch_visualization(): bool
    {
        if ($this->error) {
            return false;
        }

        try {
            // Build API endpoint
            $endpoint = Dicts::$base_uri . 'get/visualization/' . $this->vis_id;

            // Fetch raw XML response
            $xml_response = RemoteGet::retrieveContent($endpoint);

            if (is_wp_error($xml_response)) {
                $this->error = $xml_response;
                return false;
            }

            // Parse XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xml_response);
            libxml_clear_errors();

            if ($xml === false) {
                $this->error = new \WP_Error(
                    'cris-vis-xml-error',
                    __('Failed to parse visualization XML response', 'fau-cris')
                );
                return false;
            }

            // Extract generatedcontent attribute (base64+gzip encoded HTML)
            $base64_content = $this->extract_generatedcontent($xml);
            if (!$base64_content) {
                $this->error = new \WP_Error(
                    'cris-vis-content-error',
                    __('No visualization content found', 'fau-cris')
                );
                return false;
            }

            // Decode base64+gzip to get HTML
            $this->content = $this->decode_content($base64_content);
            if (!$this->content) {
                $this->error = new \WP_Error(
                    'cris-vis-decode-error',
                    __('Failed to decompress visualization content', 'fau-cris')
                );
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->error = new \WP_Error(
                'cris-vis-exception',
                sprintf(__('Visualization error: %s', 'fau-cris'), $e->getMessage())
            );
            return false;
        }
    }

    /**
     * Extract generatedcontent from XML based on page language
     * 
     * @param \SimpleXMLElement $xml Parsed XML response
     * @return string|null Base64 encoded content or null if not found
     */
    private function extract_generatedcontent(\SimpleXMLElement $xml): ?string
    {
        // Language mapping: de=1, en=2
        $lang_code = ($this->page_lang === 'en') ? '2' : '1';

        foreach ($xml->children() as $child) {
            $attr_name = (string) ($child->attributes()['name'] ?? '');
            $attr_language = (string) ($child->attributes()['language'] ?? '');

            if ($attr_name === 'generatedcontent' && $attr_language === $lang_code) {
                return (string) $child->data;
            }
        }

        return null;
    }

    /**
     * Decode base64+gzip content
     * 
     * @param string $base64_content Base64 encoded, gzip compressed content
     * @return string|null Decompressed HTML or null on error
     */
    private function decode_content(string $base64_content): ?string
    {
        // Remove whitespace from base64
        $base64_clean = preg_replace('/\s+/', '', $base64_content);

        // Decode base64
        $decoded = base64_decode($base64_clean, true);
        if ($decoded === false) {
            return null;
        }

        // Decompress gzip
        $decompressed = @gzuncompress($decoded);
        if ($decompressed === false) {
            $decompressed = @gzdecode($decoded);
        }

        if ($decompressed === false) {
            return null;
        }

        return $decompressed;
    }

    /**
     * Render the visualization
     * 
     * @param bool $fullscreen Currently unused (reserved for future use)
     * @return string HTML output or error message
     */
    public function display(bool $fullscreen = false): string
    {
        // Fetch data if not already fetched
        if (empty($this->content)) {
            if (!$this->fetch_visualization()) {
                return sprintf(
                    '<div class="cris-error">%s: %s</div>',
                    __('Visualization Error', 'fau-cris'),
                    $this->error->get_error_message()
                );
            }
        }

        // Always return iframe with fullscreen button
        return $this->render_iframe() . $this->render_fullscreen_button();
    }

    /**
     * Render iframe with sandbox attributes
     * 
     * @return string iframe HTML
     */
    private function render_iframe(): string
    {
        $height = self::SIZE_MAP[$this->size];

        // Escape content for attribute context
        $escaped_content = htmlspecialchars($this->content, ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<div class="cris-visualization cris-vis-size-%s">
                <iframe 
                    id="cris-vis-iframe-%s"
                    style="width: 100%%; height: %s; border: 2px solid #ddd; border-radius: 4px;"
                    sandbox="allow-scripts allow-popups allow-forms allow-same-origin"
                    srcdoc="%s"
                    title="%s"
                    loading="lazy"
                >
                </iframe>
            </div>',
            esc_attr($this->size),
            esc_attr($this->vis_id),
            esc_attr($height),
            $escaped_content,
            esc_attr(__('CRIS Visualization Map', 'fau-cris'))
        );
    }

    /**
     * Render fullscreen button (shown below the map)
     * 
     * @return string HTML with fullscreen button
     */
    private function render_fullscreen_button(): string
    {
        // Store content as base64
        $base64_content = base64_encode($this->content);

        return sprintf(
            '<div class="cris-visualization-fullscreen-btn" style="text-align: center; margin-top: 15px;">
                <button 
                    id="cris-fullscreen-btn-%s"
                    style="background: #002f6c; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                    🖥️ %s
                </button>
            </div>
            <script>
            (function() {
                var btn = document.getElementById("cris-fullscreen-btn-%s");
                if (btn) {
                    btn.addEventListener("click", function() {
                        var base64Content = "%s";
                        var decodedContent = atob(base64Content);
                        
                        // Open new tab with the full map
                        var newTab = window.open("", "_blank");
                        if (newTab) {
                            newTab.document.write(decodedContent);
                            newTab.document.close();
                            newTab.focus();
                        } else {
                            alert("Please disable your popup blocker to open fullscreen map");
                        }
                    });
                }
            })();
            </script>',
            esc_attr($this->vis_id),
            esc_html__('Open Fullscreen Map', 'fau-cris'),
            esc_attr($this->vis_id),
            esc_attr($base64_content),
            esc_attr($this->vis_id)
        );
    }

    /**
     * Render fullscreen button link
     * 
     * @return string HTML with button and inline script
     */
    private function render_fullscreen_link(): string
    {
        // Store content as base64 to avoid escaping issues
        $base64_content = base64_encode($this->content);

        $html = sprintf(
            '<div class="cris-visualization-fullscreen" style="text-align: center; margin: 20px 0;">
                <button 
                    id="cris-vis-fullscreen-btn-%s"
                    class="cris-vis-fullscreen-btn"
                    style="background: #002f6c; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">
                    🖥️ %s
                </button>
            </div>
            <script>
            (function() {
                var btn = document.getElementById("cris-vis-fullscreen-btn-%s");
                if (btn) {
                    btn.addEventListener("click", function() {
                        var base64Content = "%s";
                        var decodedContent = atob(base64Content);
                        
                        // Open new tab
                        var newTab = window.open("", "_blank");
                        if (newTab) {
                            newTab.document.write(decodedContent);
                            newTab.document.close();
                        }
                    });
                }
            })();
            </script>',
            esc_attr($this->vis_id),
            esc_html__('🖥️ Open Fullscreen Map', 'fau-cris'),
            esc_attr($this->vis_id),
            esc_attr($base64_content)
        );

        return $html;
    }

    /**
     * Get the current size
     * 
     * @return string Size key (s, m, or l)
     */
    public function get_size(): string
    {
        return $this->size;
    }

    /**
     * Get visualization content without rendering
     * 
     * @return string Raw HTML content
     */
    public function get_content(): string
    {
        if (empty($this->content)) {
            $this->fetch_visualization();
        }
        return $this->content;
    }

    /**
     * Check if visualization was successfully loaded
     * 
     * @return bool
     */
    public function is_valid(): bool
    {
        return !$this->error && !empty($this->content);
    }
}
