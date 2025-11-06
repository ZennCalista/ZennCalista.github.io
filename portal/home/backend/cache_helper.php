<?php
/**
 * Simple File-based Cache Helper
 * Provides basic caching functionality for API responses
 */

class SimpleCache {
    private $cache_dir;
    private $default_ttl = 300; // 5 minutes default

    public function __construct($cache_dir = null) {
        $this->cache_dir = $cache_dir ?: __DIR__ . '/cache';
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }

    /**
     * Get cached data if it exists and is not expired
     */
    public function get($key) {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        if (!$data || !isset($data['expires_at']) || !isset($data['content'])) {
            return null;
        }
        
        // Check if expired
        if (time() > $data['expires_at']) {
            @unlink($file);
            return null;
        }
        
        return $data['content'];
    }

    /**
     * Store data in cache
     */
    public function set($key, $content, $ttl = null) {
        $ttl = $ttl ?: $this->default_ttl;
        $file = $this->getCacheFile($key);
        
        $data = [
            'expires_at' => time() + $ttl,
            'created_at' => time(),
            'content' => $content
        ];
        
        return file_put_contents($file, json_encode($data)) !== false;
    }

    /**
     * Delete cached data
     */
    public function delete($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }

    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cache_dir . '/*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }

    /**
     * Clean expired cache files
     */
    public function cleanExpired() {
        $files = glob($this->cache_dir . '/*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['expires_at']) && time() > $data['expires_at']) {
                @unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }

    private function getCacheFile($key) {
        $safe_key = md5($key);
        return $this->cache_dir . '/' . $safe_key . '.cache';
    }
}
?>
