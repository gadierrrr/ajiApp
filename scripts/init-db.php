<?php
/**
 * Database Schema Initialization
 * Run once to create all tables
 *
 * Usage: php scripts/init-db.php
 */

require_once __DIR__ . '/../inc/db.php';

echo "Initializing AJI database...\n";

$db = getDB();

// Create beaches table
$db->exec('CREATE TABLE IF NOT EXISTS beaches (
    id TEXT PRIMARY KEY,
    slug TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    municipality TEXT NOT NULL,
    lat REAL NOT NULL,
    lng REAL NOT NULL,
    sargassum TEXT,
    surf TEXT,
    wind TEXT,
    cover_image TEXT NOT NULL,
    access_label TEXT,
    notes TEXT,
    description TEXT,
    parking_details TEXT,
    safety_info TEXT,
    local_tips TEXT,
    best_time TEXT,
    place_id TEXT,
    google_rating REAL,
    google_review_count INTEGER,
    publish_status TEXT DEFAULT "published",
    published_at TEXT,
    created_at TEXT,
    updated_at TEXT
)');
echo "- Created beaches table\n";

// Create indexes for beaches
$db->exec('CREATE INDEX IF NOT EXISTS idx_beaches_municipality ON beaches(municipality)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_beaches_slug ON beaches(slug)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_beaches_coords ON beaches(lat, lng)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_beaches_status ON beaches(publish_status)');
echo "- Created beaches indexes\n";

// Create beach_tags table
$db->exec('CREATE TABLE IF NOT EXISTS beach_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    beach_id TEXT NOT NULL,
    tag TEXT NOT NULL,
    FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE,
    UNIQUE(beach_id, tag)
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_beach_tags_beach ON beach_tags(beach_id)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_beach_tags_tag ON beach_tags(tag)');
echo "- Created beach_tags table\n";

// Create beach_amenities table
$db->exec('CREATE TABLE IF NOT EXISTS beach_amenities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    beach_id TEXT NOT NULL,
    amenity TEXT NOT NULL,
    FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE,
    UNIQUE(beach_id, amenity)
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_beach_amenities_beach ON beach_amenities(beach_id)');
echo "- Created beach_amenities table\n";

// Create beach_gallery table
$db->exec('CREATE TABLE IF NOT EXISTS beach_gallery (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    beach_id TEXT NOT NULL,
    image_url TEXT NOT NULL,
    position INTEGER DEFAULT 0,
    FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_beach_gallery_beach ON beach_gallery(beach_id)');
echo "- Created beach_gallery table\n";

// Create beach_aliases table
$db->exec('CREATE TABLE IF NOT EXISTS beach_aliases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    beach_id TEXT NOT NULL,
    alias TEXT NOT NULL,
    FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_beach_aliases_beach ON beach_aliases(beach_id)');
echo "- Created beach_aliases table\n";

// Create beach_features table
$db->exec('CREATE TABLE IF NOT EXISTS beach_features (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    beach_id TEXT NOT NULL,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    position INTEGER DEFAULT 0,
    FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_beach_features_beach ON beach_features(beach_id)');
echo "- Created beach_features table\n";

// Create beach_tips table
$db->exec('CREATE TABLE IF NOT EXISTS beach_tips (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    beach_id TEXT NOT NULL,
    category TEXT NOT NULL,
    tip TEXT NOT NULL,
    position INTEGER DEFAULT 0,
    FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_beach_tips_beach ON beach_tips(beach_id)');
echo "- Created beach_tips table\n";

// Create users table
$db->exec('CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    name TEXT,
    created_at TEXT,
    updated_at TEXT
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)');
echo "- Created users table\n";

// Create magic_links table
$db->exec('CREATE TABLE IF NOT EXISTS magic_links (
    id TEXT PRIMARY KEY,
    email TEXT NOT NULL,
    token TEXT UNIQUE NOT NULL,
    expires_at TEXT NOT NULL,
    used INTEGER DEFAULT 0,
    created_at TEXT
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_magic_links_token ON magic_links(token)');
echo "- Created magic_links table\n";

// Create user_favorites table
$db->exec('CREATE TABLE IF NOT EXISTS user_favorites (
    id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    beach_id TEXT NOT NULL,
    created_at TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE,
    UNIQUE(user_id, beach_id)
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_favorites_user ON user_favorites(user_id)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_favorites_beach ON user_favorites(beach_id)');
echo "- Created user_favorites table\n";

// Create rate_limits table
$db->exec('CREATE TABLE IF NOT EXISTS rate_limits (
    id TEXT PRIMARY KEY,
    identifier TEXT NOT NULL,
    action TEXT NOT NULL,
    attempts INTEGER DEFAULT 0,
    window_start TEXT NOT NULL,
    created_at TEXT
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_rate_limits ON rate_limits(identifier, action, window_start)');
echo "- Created rate_limits table\n";

echo "\nDatabase initialization complete!\n";
echo "Database location: " . envRequire('DB_PATH') . "\n";
