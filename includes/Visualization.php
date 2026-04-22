<?php
namespace RRZE\Cris;
defined('ABSPATH') || exit;

/**
 * Visualization class for rendering interactive maps from data.fau.de
 * 
 * Renders visualizations via direct iframe src to data.fau.de/visualisation/{mapid}.html
 * No API fetching required - direct URL approach
 */
class Visualization
{
    private string $vis_id;
    private string $size;
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
     * @param string $vis_id Visualization ID (mapid)
     * @param string $page_lang Page language (unused, kept for backwards compatibility)
     * @param string $size Size option ('s', 'm', or 'l')
     */
    public function __construct(string $vis_id = '', string $page_lang = 'de', string $size = self::DEFAULT_SIZE)
    {
        $this->vis_id = sanitize_text_field($vis_id);
        $this->size = isset(self::SIZE_MAP[$size]) ? $size : self::DEFAULT_SIZE;

        if (empty($this->vis_id)) {
            $this->error = new \WP_Error(
                'cris-vis-id-error',
                __('Visualization ID is required', 'fau-cris')
            );
        }
    }



    /**
     * Render the visualization
     * 
     * @param bool $fullscreen Currently unused (reserved for future use)
     * @return string HTML output or error message
     */
    public function display(bool $fullscreen = false): string
    {
        // Return error if vis_id is invalid
        if ($this->error) {
            return sprintf(
                '<div class="cris-error">%s: %s</div>',
                __('Visualization Error', 'fau-cris'),
                $this->error->get_error_message()
            );
        }

        // Return iframe with fullscreen button
        return $this->render_iframe() . $this->render_fullscreen_button();
    }

    /**
     * Render iframe with direct src to visualization URL
     * 
     * @return string iframe HTML
     */
    private function render_iframe(): string
    {
        $height = self::SIZE_MAP[$this->size];
        $url = 'https://data.fau.de/visualisation/' . esc_attr($this->vis_id) . '.html';

        return sprintf(
            '<div class="cris-visualization cris-vis-size-%s">
                <iframe 
                    id="cris-vis-iframe-%s"
                    src="%s"
                    style="width: 100%%; height: %s; border: 2px solid #ddd; border-radius: 4px;"
                    title="%s"
                    loading="lazy"
                >
                </iframe>
            </div>',
            esc_attr($this->size),
            esc_attr($this->vis_id),
            esc_url($url),
            esc_attr($height),
            esc_attr(__('CRIS Visualization Map', 'fau-cris'))
        );
    }

    /**
     * Render fullscreen button (links directly to visualization URL)
     * 
     * @return string HTML with fullscreen button
     */
    private function render_fullscreen_button(): string
    {
        $url = 'https://data.fau.de/visualisation/' . esc_attr($this->vis_id) . '.html';

        return sprintf(
            '<div class="cris-visualization-fullscreen-btn" style="text-align: center; margin-top: 15px;">
                <a 
                    href="%s"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="display: inline-block; background: #002f6c; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none;">
                    🖥️ %s
                </a>
            </div>',
            esc_url($url),
            esc_html__('Open Fullscreen Map', 'fau-cris')
        );
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
     * Get visualization URL
     * 
     * @return string Visualization URL
     */
    public function get_url(): string
    {
        if ($this->error) {
            return '';
        }
        return 'https://data.fau.de/visualisation/' . $this->vis_id . '.html';
    }

    /**
     * Check if visualization is valid (has no error)
     * 
     * @return bool
     */
    public function is_valid(): bool
    {
        return !$this->error;
    }
}
