<?php
/**
 * Admin Email Templates Management
 * Visual editor for email templates with preview and test send
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

$pageTitle = 'Email Templates';
$pageSubtitle = 'Manage and customize email templates';

include __DIR__ . '/components/header.php';

$user = currentUser();
$appName = $_ENV['APP_NAME'] ?? 'Puerto Rico Beach Finder';
$appUrl = $_ENV['APP_URL'] ?? 'https://puertoricobeachfinder.com';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_template') {
            $templateId = $_POST['template_id'] ?? '';
            $subject = trim($_POST['subject'] ?? '');
            $htmlBody = $_POST['html_body'] ?? '';
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if ($templateId && $subject && $htmlBody) {
                execute(
                    'UPDATE email_templates SET subject = :subject, html_body = :html_body, is_active = :is_active, updated_at = datetime("now") WHERE id = :id',
                    [':subject' => $subject, ':html_body' => $htmlBody, ':is_active' => $isActive, ':id' => $templateId]
                );
                $message = 'Template updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Please fill in all required fields.';
                $messageType = 'error';
            }
        } elseif ($action === 'test_send') {
            $templateId = $_POST['template_id'] ?? '';
            $testEmail = trim($_POST['test_email'] ?? '');

            if ($templateId && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                require_once APP_ROOT . '/inc/email.php';

                $template = queryOne('SELECT * FROM email_templates WHERE id = :id', [':id' => $templateId]);
                if ($template) {
                    // Replace variables with test values
                    $variables = [
                        'name' => $user['name'] ?? 'Test User',
                        'email' => $testEmail,
                        'app_name' => $appName,
                        'app_url' => $appUrl,
                        'login_url' => $appUrl . '/verify?token=test-token-preview',
                        'activity_text' => '<p style="margin: 0 0 20px; color: #fbbf24; font-size: 16px; line-height: 1.6;">Based on your preferences, we\'ll help you find the best snorkeling paradises, family-friendly beaches across Puerto Rico.</p>'
                    ];

                    $subject = $template['subject'];
                    $html = $template['html_body'];

                    foreach ($variables as $key => $value) {
                        $subject = str_replace('{{' . $key . '}}', $value, $subject);
                        $html = str_replace('{{' . $key . '}}', $value, $html);
                    }

                    if (sendEmail($testEmail, '[TEST] ' . $subject, $html, [
                        'template_slug' => (string) ($template['slug'] ?? ''),
                        'category' => 'admin_test',
                    ])) {
                        $message = 'Test email sent to ' . h($testEmail);
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to send test email. Check your email configuration.';
                        $messageType = 'error';
                    }
                }
            } else {
                $message = 'Please enter a valid email address.';
                $messageType = 'error';
            }
        }
    }
}

// Get all templates
$templates = query('SELECT * FROM email_templates ORDER BY name ASC', []);

// Get template to edit (if specified)
$editTemplate = null;
if (isset($_GET['edit'])) {
    $editTemplate = queryOne('SELECT * FROM email_templates WHERE id = :id', [':id' => $_GET['edit']]);
}
?>

<?php if ($message): ?>
<div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
    <?= h($message) ?>
</div>
<?php endif; ?>

<?php if ($editTemplate): ?>
<!-- Edit Template Form -->
<div class="bg-white rounded-xl shadow-sm mb-6">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h2 class="font-semibold text-gray-900">Edit: <?= h($editTemplate['name']) ?></h2>
            <p class="text-sm text-gray-500 mt-1"><?= h($editTemplate['description']) ?></p>
        </div>
        <a href="/admin/emails" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </a>
    </div>

    <form method="POST" action="" class="p-6">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_template">
        <input type="hidden" name="template_id" value="<?= h($editTemplate['id']) ?>">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Editor Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Subject -->
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Subject
                    </label>
                    <input type="text"
                           id="subject"
                           name="subject"
                           value="<?= h($editTemplate['subject']) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           required>
                </div>

                <!-- HTML Body -->
                <div>
                    <label for="html_body" class="block text-sm font-medium text-gray-700 mb-2">
                        HTML Body
                    </label>
                    <textarea id="html_body"
                              name="html_body"
                              rows="25"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              required><?= h($editTemplate['html_body']) ?></textarea>
                </div>

                <!-- Active Toggle -->
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           id="is_active"
                           name="is_active"
                           value="1"
                           <?= $editTemplate['is_active'] ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="is_active" class="text-sm text-gray-700">
                        Template is active (will be used when sending emails)
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4 pt-4 border-t">
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        Save Changes
                    </button>
                    <a href="/admin/emails" class="text-gray-600 hover:text-gray-800">
                        Cancel
                    </a>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Variables Panel -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        Available Variables
                    </h3>
                    <div class="space-y-2">
                        <?php
                        $variables = json_decode($editTemplate['variables'] ?? '[]', true) ?: [];
                        foreach ($variables as $var):
                        ?>
                        <div class="bg-white rounded border border-gray-200 p-2">
                            <code class="text-sm text-blue-600 font-mono block">{{<?= h($var['name']) ?>}}</code>
                            <p class="text-xs text-gray-500 mt-1"><?= h($var['description']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        Click to copy, then paste into your template
                    </p>
                </div>

                <!-- Test Send -->
                <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                    <h3 class="font-medium text-yellow-800 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Send Test Email
                    </h3>
                    <p class="text-sm text-yellow-700 mb-3">
                        Send a test email to preview how it looks
                    </p>
                    <div class="space-y-3">
                        <input type="email"
                               form="test-form"
                               name="test_email"
                               placeholder="your@email.com"
                               value="<?= h($user['email']) ?>"
                               class="w-full px-3 py-2 text-sm border border-yellow-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                        <button type="submit"
                                form="test-form"
                                class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            Send Test
                        </button>
                    </div>
                </div>

                <!-- Preview Button -->
                <button type="button"
                        onclick="showPreview()"
                        class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Preview Email
                </button>

                <!-- Info -->
                <div class="text-xs text-gray-500 space-y-1">
                    <p><strong>Template ID:</strong> <?= h($editTemplate['slug']) ?></p>
                    <p><strong>Last updated:</strong> <?= date('M j, Y g:i A', strtotime($editTemplate['updated_at'])) ?></p>
                </div>
            </div>
        </div>
    </form>

    <!-- Test Send Form (separate to avoid nested forms) -->
    <form id="test-form" method="POST" action="" class="hidden">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="test_send">
        <input type="hidden" name="template_id" value="<?= h($editTemplate['id']) ?>">
    </form>
</div>

<!-- Preview Modal -->
<div id="preview-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Email Preview</h3>
            <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-auto p-4 bg-gray-100">
            <iframe id="preview-frame" class="w-full h-full min-h-[600px] bg-white rounded shadow" sandbox="allow-same-origin"></iframe>
        </div>
    </div>
</div>

<script>
function showPreview() {
    const html = document.getElementById('html_body').value;
    const variables = {
        'name': '<?= h($user['name'] ?? 'Test User') ?>',
        'email': '<?= h($user['email']) ?>',
        'app_name': '<?= h($appName) ?>',
        'app_url': '<?= h($appUrl) ?>',
        'login_url': '<?= h($appUrl) ?>/verify?token=test-preview',
        'activity_text': '<p style="margin: 0 0 20px; color: #fbbf24; font-size: 16px; line-height: 1.6;">Based on your preferences, we\'ll help you find the best snorkeling paradises, family-friendly beaches across Puerto Rico.</p>'
    };

    let preview = html;
    for (const [key, value] of Object.entries(variables)) {
        preview = preview.split('{{' + key + '}}').join(value);
    }

    const frame = document.getElementById('preview-frame');
    frame.srcdoc = preview;

    document.getElementById('preview-modal').classList.remove('hidden');
    document.getElementById('preview-modal').classList.add('flex');
}

function closePreview() {
    document.getElementById('preview-modal').classList.add('hidden');
    document.getElementById('preview-modal').classList.remove('flex');
}

// Close modal on escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePreview();
});

// Copy variable on click
document.querySelectorAll('code').forEach(el => {
    el.style.cursor = 'pointer';
    el.addEventListener('click', () => {
        navigator.clipboard.writeText(el.textContent);
        el.classList.add('bg-green-100');
        setTimeout(() => el.classList.remove('bg-green-100'), 500);
    });
});
</script>

<?php else: ?>
<!-- Templates List -->
<div class="bg-white rounded-xl shadow-sm">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="font-semibold text-gray-900">All Templates</h2>
    </div>

    <div class="divide-y divide-gray-100">
        <?php if (empty($templates)): ?>
        <div class="p-8 text-center text-gray-500">
            No email templates found. Run the migration to create default templates.
        </div>
        <?php else: ?>
        <?php foreach ($templates as $template): ?>
        <div class="p-6 hover:bg-gray-50 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-medium text-gray-900"><?= h($template['name']) ?></h3>
                        <?php if ($template['is_active']): ?>
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Active</span>
                        <?php else: ?>
                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Inactive</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm text-gray-500 mt-1"><?= h($template['description']) ?></p>
                    <p class="text-xs text-gray-400 mt-1">
                        Last updated: <?= date('M j, Y', strtotime($template['updated_at'])) ?>
                    </p>
                </div>
            </div>
            <a href="?edit=<?= h($template['id']) ?>"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Edit Template
            </a>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Help Section -->
<div class="mt-6 bg-blue-50 a11y-on-light-blue rounded-xl p-6 border border-blue-200">
    <h3 class="font-semibold text-blue-900 mb-2">About Email Templates</h3>
    <div class="text-sm text-blue-800 space-y-2">
        <p>Email templates use <code class="bg-blue-100 px-1 rounded">{{variable}}</code> syntax for dynamic content.</p>
        <p>Each template shows available variables in the editor sidebar. Common variables include:</p>
        <ul class="list-disc list-inside ml-2 space-y-1">
            <li><code class="bg-blue-100 px-1 rounded">{{name}}</code> - Recipient's name</li>
            <li><code class="bg-blue-100 px-1 rounded">{{email}}</code> - Recipient's email</li>
            <li><code class="bg-blue-100 px-1 rounded">{{app_name}}</code> - Your app name</li>
            <li><code class="bg-blue-100 px-1 rounded">{{app_url}}</code> - Your app URL</li>
        </ul>
        <p class="mt-3">Use the "Send Test" feature to preview emails before they go to real users.</p>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/components/footer.php'; ?>
