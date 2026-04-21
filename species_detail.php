<?php
include "config/database.php";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM species WHERE species_id=? AND status='Approved'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo "Species not found";
    exit;
}

$rel_stmt = $conn->prepare("SELECT species_id, species_name, image_path, threat_level FROM species WHERE species_type=? AND species_id!=? AND status='Approved' LIMIT 3");
$rel_stmt->bind_param("si", $row['species_type'], $id);
$rel_stmt->execute();
$related = $rel_stmt->get_result();

function threatColor($l) {
    if ($l==='Endangered')      return '#ff4d4d';
    if ($l==='Vulnerable')      return '#f59e0b';
    if ($l==='Near Threatened') return '#fb923c';
    return '#22c55e';
}
function threatBg($l) {
    if ($l==='Endangered')      return 'rgba(255,77,77,0.15)';
    if ($l==='Vulnerable')      return 'rgba(245,158,11,0.15)';
    if ($l==='Near Threatened') return 'rgba(251,146,60,0.15)';
    return 'rgba(34,197,94,0.15)';
}
function threatPointer($l) {
    if ($l==='Endangered')      return '85%';
    if ($l==='Vulnerable')      return '58%';
    if ($l==='Near Threatened') return '38%';
    return '10%';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($row['species_name']); ?> — GreenAtlas</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <style>
        :root {
            --gm: #52b788; --gl: #b7e4c7; --bg: #0b0f14;
            --card: rgba(255,255,255,0.04); --border: rgba(255,255,255,0.08);
            --text: #e6fff2; --muted: #8aab99;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Poppins',sans-serif;background:var(--bg);color:var(--text);overflow-x:hidden;animation:fadeIn .7s ease}
        @keyframes fadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}

        /* NAVBAR */
        .navbar{position:fixed;top:16px;left:50%;transform:translateX(-50%);width:95%;max-width:1200px;display:flex;justify-content:space-between;align-items:center;padding:10px 20px;background:rgba(11,15,20,.75);backdrop-filter:blur(16px);border-radius:50px;border:1px solid var(--border);z-index:999}
        .logo{font-size:20px;font-weight:700;background:linear-gradient(90deg,#00ffcc,#52b788);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none}
        .nav-back{display:flex;align-items:center;gap:8px;color:var(--gl);text-decoration:none;font-size:14px;padding:8px 16px;border-radius:25px;border:1px solid var(--border);background:var(--card);transition:.3s}
        .nav-back:hover{background:rgba(82,183,136,.15);border-color:var(--gm)}

        /* HERO */
        .hero{position:relative;height:75vh;min-height:480px;overflow:hidden}
        .hero-img{width:100%;height:100%;object-fit:cover;filter:brightness(.55) saturate(1.1);transition:transform 8s ease}
        .hero:hover .hero-img{transform:scale(1.04)}
        .hero-gradient{position:absolute;inset:0;background:linear-gradient(to bottom,rgba(11,15,20,.1) 0%,rgba(11,15,20,.3) 40%,rgba(11,15,20,.97) 100%)}
        .hero-content{position:absolute;bottom:48px;left:48px;right:48px}
        .type-pill{display:inline-block;font-size:11px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--gm);background:rgba(82,183,136,.15);border:1px solid rgba(82,183,136,.3);padding:5px 14px;border-radius:25px;margin-bottom:14px}
        .hero-content h1{font-family:'Playfair Display',serif;font-size:clamp(38px,6vw,68px);line-height:1.1;color:#fff;text-shadow:0 4px 30px rgba(0,0,0,.5);margin-bottom:8px}
        .sci-name{font-size:16px;color:var(--muted);font-style:italic;margin-bottom:18px}
        .threat-badge{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:25px;font-size:13px;font-weight:600;border:1px solid}

        /* BODY */
        .page-body{max-width:1000px;margin:0 auto;padding:40px 24px 80px}

        /* STAT CARDS */
        .stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:32px}
        .stat-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:20px;text-align:center;transition:.3s}
        .stat-card:hover{border-color:rgba(82,183,136,.3);background:rgba(82,183,136,.05);transform:translateY(-3px)}
        .stat-icon{font-size:22px;margin-bottom:8px;color:var(--gm)}
        .stat-label{font-size:10px;color:var(--muted);letter-spacing:1px;text-transform:uppercase;margin-bottom:4px}
        .stat-value{font-size:13px;font-weight:600;color:var(--text)}

        /* SECTION */
        .section-card{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:28px;margin-bottom:20px;transition:border-color .3s}
        .section-card:hover{border-color:rgba(82,183,136,.2)}
        .section-card h2{font-size:11px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;color:var(--gm);margin-bottom:16px;display:flex;align-items:center;gap:8px}
        .desc-text{font-size:15px;line-height:1.85;color:#c8ddd4}

        /* INFO GRID */
        .info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:14px}
        .info-item{background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:12px;padding:18px;display:flex;gap:14px;align-items:flex-start;transition:.3s}
        .info-item:hover{border-color:rgba(82,183,136,.25);background:rgba(82,183,136,.04)}
        .info-icon{width:40px;height:40px;border-radius:10px;background:rgba(82,183,136,.12);display:flex;align-items:center;justify-content:center;color:var(--gm);font-size:16px;flex-shrink:0}
        .info-label{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:4px}
        .info-value{font-size:14px;color:var(--text);line-height:1.5}

        /* FUN FACT */
        .fun-fact-box{display:flex;gap:16px;align-items:flex-start;background:rgba(82,183,136,.06);border:1px solid rgba(82,183,136,.2);border-radius:14px;padding:22px}
        .fun-fact-emoji{font-size:30px;flex-shrink:0;margin-top:2px}
        .fun-fact-text{font-size:15px;line-height:1.8;color:#c8ddd4;font-style:italic}

        /* CONSERVATION SCALE */
        .scale-labels{display:flex;justify-content:space-between;font-size:10px;color:var(--muted);margin-bottom:8px}
        .scale-track{height:8px;border-radius:8px;background:linear-gradient(90deg,#22c55e,#f59e0b,#ff4d4d);position:relative}
        .scale-pointer{position:absolute;top:-5px;width:18px;height:18px;border-radius:50%;background:white;border:3px solid var(--bg);box-shadow:0 0 10px rgba(255,255,255,.5);transform:translateX(-50%);transition:left 1.2s cubic-bezier(.34,1.56,.64,1);left:10%}

        /* MAP */
        #map{height:300px;border-radius:12px;overflow:hidden}

        /* RELATED */
        .related-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-top:4px}
        .related-card{border-radius:14px;overflow:hidden;text-decoration:none;background:var(--card);border:1px solid var(--border);transition:.3s;display:block}
        .related-card:hover{transform:translateY(-6px);border-color:rgba(82,183,136,.4);box-shadow:0 12px 30px rgba(0,0,0,.4)}
        .related-card img{width:100%;height:150px;object-fit:cover}
        .related-card-body{padding:12px 14px}
        .related-card-body h4{font-size:14px;font-weight:600;margin-bottom:4px;color:var(--text)}

        /* SHARE */
        .share-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 24px;border-radius:25px;background:rgba(82,183,136,.1);border:1px solid rgba(82,183,136,.3);color:var(--gl);font-size:13px;font-family:'Poppins',sans-serif;cursor:pointer;transition:.3s;margin-top:8px}
        .share-btn:hover{background:rgba(82,183,136,.2);transform:translateY(-2px)}

        /* FOOTER */
        .mini-footer{text-align:center;padding:30px;color:var(--muted);font-size:13px;border-top:1px solid var(--border);margin-top:20px}

        @media(max-width:600px){
            .hero-content{left:20px;right:20px;bottom:30px}
            .hero-content h1{font-size:36px}
            .page-body{padding:24px 16px 60px}
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">GreenAtlas 🌿</a>
    <a href="index.php#explore" class="nav-back"><i class="fas fa-arrow-left"></i> Back to Explore</a>
</nav>

<div class="hero">
    <img class="hero-img"
         src="uploads/<?php echo htmlspecialchars($row['image_path'] ?: 'default.jpg'); ?>"
         onerror="this.src='uploads/default.jpg'"
         alt="<?php echo htmlspecialchars($row['species_name']); ?>">
    <div class="hero-gradient"></div>
    <div class="hero-content">
        <div class="type-pill"><i class="fas fa-paw"></i> <?php echo htmlspecialchars($row['species_type']); ?></div>
        <h1><?php echo htmlspecialchars($row['species_name']); ?></h1>
        <p class="sci-name"><?php echo htmlspecialchars($row['scientific_name']); ?></p>
        <span class="threat-badge" style="color:<?php echo threatColor($row['threat_level']); ?>;background:<?php echo threatBg($row['threat_level']); ?>;border-color:<?php echo threatColor($row['threat_level']); ?>44;">
            <?php if($row['threat_level']==='Endangered'): ?><i class="fas fa-exclamation-triangle"></i>
            <?php elseif(in_array($row['threat_level'],['Vulnerable','Near Threatened'])): ?><i class="fas fa-exclamation-circle"></i>
            <?php else: ?><i class="fas fa-check-circle"></i><?php endif; ?>
            <?php echo htmlspecialchars($row['threat_level']); ?>
        </span>
    </div>
</div>

<div class="page-body">

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-tag"></i></div>
            <div class="stat-label">Class</div>
            <div class="stat-value"><?php echo htmlspecialchars($row['species_type']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="stat-label">Status</div>
            <div class="stat-value" style="color:<?php echo threatColor($row['threat_level']); ?>"><?php echo htmlspecialchars($row['threat_level']); ?></div>
        </div>
        <?php if(!empty($row['lifespan'])): ?>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-label">Lifespan</div>
            <div class="stat-value"><?php echo htmlspecialchars($row['lifespan']); ?></div>
        </div>
        <?php endif; ?>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-flask"></i></div>
            <div class="stat-label">Scientific name</div>
            <div class="stat-value" style="font-style:italic;font-size:11px;"><?php echo htmlspecialchars($row['scientific_name'] ?: '—'); ?></div>
        </div>
    </div>

    <!-- ABOUT -->
    <div class="section-card">
        <h2><i class="fas fa-align-left"></i> About this species</h2>
        <p class="desc-text"><?php echo nl2br(htmlspecialchars($row['description'] ?: 'No description available.')); ?></p>
    </div>

    <!-- PROFILE: HABITAT / DIET / LIFESPAN -->
    <?php if(!empty($row['habitat']) || !empty($row['diet']) || !empty($row['lifespan'])): ?>
    <div class="section-card">
        <h2><i class="fas fa-info-circle"></i> Species profile</h2>
        <div class="info-grid">
            <?php if(!empty($row['habitat'])): ?>
            <div class="info-item">
                <div class="info-icon"><i class="fas fa-tree"></i></div>
                <div>
                    <div class="info-label">Habitat</div>
                    <div class="info-value"><?php echo htmlspecialchars($row['habitat']); ?></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if(!empty($row['diet'])): ?>
            <div class="info-item">
                <div class="info-icon"><i class="fas fa-drumstick-bite"></i></div>
                <div>
                    <div class="info-label">Diet</div>
                    <div class="info-value"><?php echo htmlspecialchars($row['diet']); ?></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if(!empty($row['lifespan'])): ?>
            <div class="info-item">
                <div class="info-icon"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <div class="info-label">Lifespan</div>
                    <div class="info-value"><?php echo htmlspecialchars($row['lifespan']); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- FUN FACT -->
    <?php if(!empty($row['fun_fact'])): ?>
    <div class="section-card">
        <h2><i class="fas fa-lightbulb"></i> Did you know?</h2>
        <div class="fun-fact-box">
            <div class="fun-fact-emoji">💡</div>
            <p class="fun-fact-text"><?php echo htmlspecialchars($row['fun_fact']); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- CONSERVATION -->
    <div class="section-card">
        <h2><i class="fas fa-leaf"></i> Conservation status</h2>
        <p class="desc-text" style="margin-bottom:18px;">
            <?php
            if ($row['threat_level']==='Endangered')
                echo "This species is <strong style='color:#ff4d4d'>Endangered</strong> — facing a very high risk of extinction in the wild. Immediate conservation action is critical.";
            elseif ($row['threat_level']==='Vulnerable')
                echo "This species is <strong style='color:#f59e0b'>Vulnerable</strong> — facing a high risk of extinction if threatening factors continue to operate.";
            elseif ($row['threat_level']==='Near Threatened')
                echo "This species is <strong style='color:#fb923c'>Near Threatened</strong> — close to qualifying for a threatened category and may be at risk in the near future.";
            else
                echo "This species is currently of <strong style='color:#22c55e'>Low Concern</strong> — populations are stable and not under immediate threat.";
            ?>
        </p>
        <div class="scale-labels">
            <span>Least concern</span><span>Near threatened</span><span>Vulnerable</span><span>Endangered</span>
        </div>
        <div class="scale-track">
            <div class="scale-pointer" id="scalePtr"></div>
        </div>
    </div>

    <!-- MAP -->
    <?php if(!empty($row['latitude']) && !empty($row['longitude'])): ?>
    <div class="section-card">
        <h2><i class="fas fa-map-marked-alt"></i> Known habitat location</h2>
        <div id="map"></div>
    </div>
    <?php endif; ?>

    <!-- RELATED -->
    <?php if($related->num_rows > 0): ?>
    <div class="section-card">
        <h2><i class="fas fa-th-large"></i> More <?php echo htmlspecialchars($row['species_type']); ?>s</h2>
        <div class="related-grid">
            <?php while($rel = $related->fetch_assoc()): ?>
            <a href="species_detail.php?id=<?php echo $rel['species_id']; ?>" class="related-card">
                <img src="uploads/<?php echo htmlspecialchars($rel['image_path'] ?: 'default.jpg'); ?>"
                     onerror="this.src='uploads/default.jpg'"
                     alt="<?php echo htmlspecialchars($rel['species_name']); ?>">
                <div class="related-card-body">
                    <h4><?php echo htmlspecialchars($rel['species_name']); ?></h4>
                    <span style="font-size:12px;font-weight:600;color:<?php echo threatColor($rel['threat_level']); ?>"><?php echo htmlspecialchars($rel['threat_level']); ?></span>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <button class="share-btn" onclick="shareSpecies()">
        <i class="fas fa-share-alt"></i> Share this species
    </button>

</div>

<div class="mini-footer">
    &copy; <?php echo date('Y'); ?> GreenAtlas — Exploring and protecting wildlife across the globe 🌿
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    <?php if(!empty($row['latitude']) && !empty($row['longitude'])): ?>
    var map = L.map('map',{zoomControl:true,scrollWheelZoom:false})
        .setView([<?php echo $row['latitude']; ?>,<?php echo $row['longitude']; ?>],6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap contributors'}).addTo(map);
    L.marker([<?php echo $row['latitude']; ?>,<?php echo $row['longitude']; ?>])
        .addTo(map)
        .bindPopup('<b><?php echo htmlspecialchars($row['species_name']); ?></b><br><i><?php echo htmlspecialchars($row['species_type']); ?></i>')
        .openPopup();
    <?php endif; ?>

    setTimeout(function(){
        var p = document.getElementById('scalePtr');
        if(p) p.style.left = '<?php echo threatPointer($row['threat_level']); ?>';
    }, 400);

    function shareSpecies(){
        if(navigator.share){
            navigator.share({title:'<?php echo htmlspecialchars($row['species_name']); ?> — GreenAtlas',url:window.location.href});
        } else {
            navigator.clipboard.writeText(window.location.href);
            alert('Link copied!');
        }
    }
</script>
</body>
</html>