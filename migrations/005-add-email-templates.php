<?php
/**
 * Migration: Add email templates table
 * Allows editing email templates via admin dashboard
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: Email templates\n";

$db = getDb();

// ============================================================
// EMAIL TEMPLATES TABLE
// ============================================================
$db->exec("
    CREATE TABLE IF NOT EXISTS email_templates (
        id TEXT PRIMARY KEY,
        slug TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL,
        subject TEXT NOT NULL,
        html_body TEXT NOT NULL,
        description TEXT,
        variables TEXT,  -- JSON array of available variables
        is_active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )
");
echo "✓ Created email_templates table\n";

// ============================================================
// INSERT DEFAULT TEMPLATES
// ============================================================

// Welcome email template
$welcomeId = 'tpl_' . bin2hex(random_bytes(8));
$welcomeVariables = json_encode([
    ['name' => 'name', 'description' => 'User\'s display name'],
    ['name' => 'email', 'description' => 'User\'s email address'],
    ['name' => 'app_name', 'description' => 'Application name (AJI)'],
    ['name' => 'app_url', 'description' => 'Application URL'],
    ['name' => 'activity_text', 'description' => 'Personalized activity recommendations (auto-generated)']
]);

$welcomeHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" style="max-width: 600px; background-color: #1e293b; border-radius: 16px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #0f172a; font-size: 28px; font-weight: bold;">
                                Welcome to {{app_name}}!
                            </h1>
                            <p style="margin: 10px 0 0; color: #0f172a; opacity: 0.8; font-size: 16px;">
                                Your beach adventure starts now
                            </p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 20px; color: #e2e8f0; font-size: 16px; line-height: 1.6;">
                                Hey {{name}}! 👋
                            </p>

                            <p style="margin: 0 0 20px; color: #94a3b8; font-size: 16px; line-height: 1.6;">
                                Thanks for joining our community of beach lovers exploring Puerto Rico's 230+ beaches!
                            </p>

                            {{activity_text}}

                            <!-- Features Grid -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td width="50%" style="padding: 10px;">
                                        <div style="background-color: #334155; border-radius: 12px; padding: 20px; text-align: center;">
                                            <div style="font-size: 32px; margin-bottom: 10px;">🌤️</div>
                                            <div style="color: #e2e8f0; font-weight: 600; font-size: 14px;">Real-time Weather</div>
                                            <div style="color: #64748b; font-size: 12px; margin-top: 5px;">Know before you go</div>
                                        </div>
                                    </td>
                                    <td width="50%" style="padding: 10px;">
                                        <div style="background-color: #334155; border-radius: 12px; padding: 20px; text-align: center;">
                                            <div style="font-size: 32px; margin-bottom: 10px;">❤️</div>
                                            <div style="color: #e2e8f0; font-weight: 600; font-size: 14px;">Save Favorites</div>
                                            <div style="color: #64748b; font-size: 12px; margin-top: 5px;">Build your bucket list</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="50%" style="padding: 10px;">
                                        <div style="background-color: #334155; border-radius: 12px; padding: 20px; text-align: center;">
                                            <div style="font-size: 32px; margin-bottom: 10px;">🏆</div>
                                            <div style="color: #e2e8f0; font-weight: 600; font-size: 14px;">Earn Badges</div>
                                            <div style="color: #64748b; font-size: 12px; margin-top: 5px;">Track your journey</div>
                                        </div>
                                    </td>
                                    <td width="50%" style="padding: 10px;">
                                        <div style="background-color: #334155; border-radius: 12px; padding: 20px; text-align: center;">
                                            <div style="font-size: 32px; margin-bottom: 10px;">📸</div>
                                            <div style="color: #e2e8f0; font-weight: 600; font-size: 14px;">Share Photos</div>
                                            <div style="color: #64748b; font-size: 12px; margin-top: 5px;">Help others discover</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{{app_url}}" style="display: inline-block; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #0f172a; text-decoration: none; padding: 16px 40px; border-radius: 12px; font-weight: 600; font-size: 16px;">
                                            Start Exploring Beaches
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Tips -->
                            <div style="background-color: #334155; border-radius: 12px; padding: 20px; margin-top: 20px;">
                                <p style="margin: 0 0 10px; color: #fbbf24; font-weight: 600; font-size: 14px;">
                                    💡 Pro tip
                                </p>
                                <p style="margin: 0; color: #94a3b8; font-size: 14px; line-height: 1.6;">
                                    Check in at beaches you visit to help others know current crowd levels and conditions. You'll also earn progress toward your Explorer badges!
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px; border-top: 1px solid #334155; text-align: center;">
                            <p style="margin: 0 0 10px; color: #64748b; font-size: 14px;">
                                Happy exploring! 🏖️
                            </p>
                            <p style="margin: 0; color: #475569; font-size: 12px;">
                                <a href="{{app_url}}" style="color: #fbbf24; text-decoration: none;">{{app_name}}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

$stmt = $db->prepare("
    INSERT OR IGNORE INTO email_templates (id, slug, name, subject, html_body, description, variables)
    VALUES (:id, :slug, :name, :subject, :html_body, :description, :variables)
");
$stmt->bindValue(':id', $welcomeId, SQLITE3_TEXT);
$stmt->bindValue(':slug', 'welcome', SQLITE3_TEXT);
$stmt->bindValue(':name', 'Welcome Email', SQLITE3_TEXT);
$stmt->bindValue(':subject', 'Welcome to {{app_name}}! 🏖️', SQLITE3_TEXT);
$stmt->bindValue(':html_body', $welcomeHtml, SQLITE3_TEXT);
$stmt->bindValue(':description', 'Sent to new users when they first register', SQLITE3_TEXT);
$stmt->bindValue(':variables', $welcomeVariables, SQLITE3_TEXT);
$stmt->execute();
echo "✓ Added welcome email template\n";

// Magic link email template
$magicLinkId = 'tpl_' . bin2hex(random_bytes(8));
$magicLinkVariables = json_encode([
    ['name' => 'login_url', 'description' => 'Magic link login URL'],
    ['name' => 'app_name', 'description' => 'Application name'],
    ['name' => 'app_url', 'description' => 'Application URL']
]);

$magicLinkHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" style="max-width: 600px; background-color: #1e293b; border-radius: 16px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); padding: 30px; text-align: center;">
                            <h1 style="margin: 0; color: #0f172a; font-size: 24px; font-weight: bold;">
                                Sign in to {{app_name}}
                            </h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 20px; color: #e2e8f0; font-size: 16px; line-height: 1.6;">
                                Click the button below to sign in to your account:
                            </p>

                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{{login_url}}" style="display: inline-block; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #0f172a; text-decoration: none; padding: 16px 40px; border-radius: 12px; font-weight: 600; font-size: 16px;">
                                            Sign In
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 20px 0 0; color: #64748b; font-size: 14px; line-height: 1.6;">
                                Or copy and paste this link into your browser:
                            </p>
                            <p style="margin: 10px 0 0; color: #94a3b8; font-size: 12px; word-break: break-all;">
                                {{login_url}}
                            </p>

                            <div style="background-color: #334155; border-radius: 12px; padding: 16px; margin-top: 30px;">
                                <p style="margin: 0; color: #94a3b8; font-size: 13px; line-height: 1.5;">
                                    ⏰ This link expires in 15 minutes.<br>
                                    🔒 If you didn't request this, you can safely ignore this email.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 30px; border-top: 1px solid #334155; text-align: center;">
                            <p style="margin: 0; color: #475569; font-size: 12px;">
                                <a href="{{app_url}}" style="color: #fbbf24; text-decoration: none;">{{app_name}}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

$stmt = $db->prepare("
    INSERT OR IGNORE INTO email_templates (id, slug, name, subject, html_body, description, variables)
    VALUES (:id, :slug, :name, :subject, :html_body, :description, :variables)
");
$stmt->bindValue(':id', $magicLinkId, SQLITE3_TEXT);
$stmt->bindValue(':slug', 'magic-link', SQLITE3_TEXT);
$stmt->bindValue(':name', 'Magic Link Login', SQLITE3_TEXT);
$stmt->bindValue(':subject', 'Sign in to {{app_name}}', SQLITE3_TEXT);
$stmt->bindValue(':html_body', $magicLinkHtml, SQLITE3_TEXT);
$stmt->bindValue(':description', 'Sent when users request a magic link to sign in', SQLITE3_TEXT);
$stmt->bindValue(':variables', $magicLinkVariables, SQLITE3_TEXT);
$stmt->execute();
echo "✓ Added magic link email template\n";

echo "\n✅ Migration completed successfully!\n";
