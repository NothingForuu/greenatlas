<?php
// ============================================
// GREENATLAS - AUTO IMAGE DOWNLOADER
// Place this file in your greenatlas folder
// Open in browser: localhost/greenatlas/download_images.php
// ============================================

include "config/database.php";

// Fetch all species that have default or missing images
$result = $conn->query("SELECT species_id, species_name, scientific_name, image_path FROM species WHERE status='Approved'");

$downloaded = 0;
$skipped    = 0;
$failed     = 0;
$log        = [];

while ($row = $result->fetch_assoc()) {
    $image_path = "uploads/" . $row['image_path'];

    // Skip if image already exists and is not default
    if (file_exists($image_path) && $row['image_path'] !== 'default.jpg' && filesize($image_path) > 5000) {
        $skipped++;
        $log[] = "⏭️ Skipped (exists): " . $row['species_name'];
        continue;
    }

    // Try scientific name first, then common name
    $search_terms = [
        $row['scientific_name'],
        $row['species_name'],
        $row['species_name'] . " animal"
    ];

    $image_url = null;

    foreach ($search_terms as $term) {
        $api_url = "https://en.wikipedia.org/w/api.php?" . http_build_query([
            'action'      => 'query',
            'titles'      => $term,
            'prop'        => 'pageimages',
            'format'      => 'json',
            'pithumbsize' => 800,
            'redirects'   => 1
        ]);

        $context = stream_context_create([
            'http' => [
                'timeout'     => 10,
                'user_agent'  => 'GreenAtlas/1.0 (educational project)'
            ]
        ]);

        $response = @file_get_contents($api_url, false, $context);
        if (!$response) continue;

        $data  = json_decode($response, true);
        $pages = $data['query']['pages'] ?? [];
        $page  = reset($pages);

        if (!empty($page['thumbnail']['source'])) {
            $image_url = $page['thumbnail']['source'];
            break;
        }
    }

    if (!$image_url) {
        $failed++;
        $log[] = "❌ No image found: " . $row['species_name'];
        continue;
    }

    // Download the image
    $image_data = @file_get_contents($image_url, false, $context);
    if (!$image_data) {
        $failed++;
        $log[] = "❌ Download failed: " . $row['species_name'];
        continue;
    }

    // Detect extension from URL
    $ext = 'jpg';
    if (strpos($image_url, '.png') !== false) $ext = 'png';
    if (strpos($image_url, '.jpeg') !== false) $ext = 'jpg';

    // Generate filename from scientific name
    $filename = strtolower(str_replace([' ', "'", '.'], ['_', '', ''], $row['scientific_name'])) . '.' . $ext;
    $save_path = "uploads/" . $filename;

    if (file_put_contents($save_path, $image_data)) {
        // Update DB with new image path
        $stmt = $conn->prepare("UPDATE species SET image_path=? WHERE species_id=?");
        $stmt->bind_param("si", $filename, $row['species_id']);
        $stmt->execute();

        $downloaded++;
        $log[] = "✅ Downloaded: " . $row['species_name'] . " → " . $filename;
    } else {
        $failed++;
        $log[] = "❌ Save failed: " . $row['species_name'];
    }

    // Small delay to be kind to Wikipedia's servers
    usleep(300000); // 0.3 seconds
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Downloader — GreenAtlas</title>
    <style>
        body { font-family: Poppins, sans-serif; background: #0b1d13; color: white; padding: 30px; }
        h1 { color: #52b788; }
        .summary { display: flex; gap: 20px; margin: 20px 0; }
        .box { padding: 20px 30px; border-radius: 12px; text-align: center; }
        .green { background: rgba(34,197,94,0.15); border: 1px solid #22c55e; }
        .orange { background: rgba(245,158,11,0.15); border: 1px solid #f59e0b; }
        .red { background: rgba(255,77,77,0.15); border: 1px solid #ff4d4d; }
        .num { font-size: 36px; font-weight: 700; }
        .log { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 20px; max-height: 500px; overflow-y: auto; }
        .log p { margin: 4px 0; font-size: 13px; color: #b7e4c7; }
        a { color: #52b788; }
    </style>
</head>
<body>
    <h1>🖼️ GreenAtlas Image Downloader</h1>

    <div class="summary">
        <div class="box green">
            <div class="num"><?php echo $downloaded; ?></div>
            <div>Downloaded</div>
        </div>
        <div class="box orange">
            <div class="num"><?php echo $skipped; ?></div>
            <div>Skipped (already exist)</div>
        </div>
        <div class="box red">
            <div class="num"><?php echo $failed; ?></div>
            <div>Failed</div>
        </div>
    </div>

    <div class="log">
        <?php foreach ($log as $entry): ?>
            <p><?php echo htmlspecialchars($entry); ?></p>
        <?php endforeach; ?>
    </div>

    <br>
    <a href="index.php">← Back to GreenAtlas</a>
    <?php if ($failed > 0): ?>
    <br><br>
    <p style="color:#f59e0b;">⚠️ <?php echo $failed; ?> species had no image found. You can manually add images for these in your uploads folder.</p>
    <?php endif; ?>
</body>
</html>