<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
include "config/database.php";
 
/* STATS FOR HERO */
$total_species   = $conn->query("SELECT COUNT(*) FROM species WHERE status='Approved'")->fetch_row()[0];
$endangered_count= $conn->query("SELECT COUNT(*) FROM species WHERE status='Approved' AND threat_level='Endangered'")->fetch_row()[0];
$vulnerable_count= $conn->query("SELECT COUNT(*) FROM species WHERE status='Approved' AND threat_level='Vulnerable'")->fetch_row()[0];
$types_count     = $conn->query("SELECT COUNT(DISTINCT species_type) FROM species WHERE status='Approved'")->fetch_row()[0];

$search       = $_GET['search'] ?? "";
$type_filter  = $_GET['type']   ?? "";
$threat_filter= $_GET['threat'] ?? "";

/* MAIN QUERY */
$sql = "SELECT * FROM species WHERE status='Approved'";
$params = [];
$types  = "";

/* SEARCH */
if (!empty($search)) {
    $sql .= " AND (species_name LIKE ? OR scientific_name LIKE ? OR species_type LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

/* TYPE FILTER */
if (!empty($type_filter)) {
    $sql .= " AND species_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

/* THREAT FILTER */
if (!empty($threat_filter)) {
    $sql .= " AND threat_level = ?";
    $params[] = $threat_filter;
    $types .= "s";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

/* MAP DATA */
$map_data  = [];
$map_query = $conn->query("
    SELECT species_id, species_name, species_type, latitude, longitude, threat_level, image_path
    FROM species
    WHERE status='Approved'
      AND latitude IS NOT NULL
      AND longitude IS NOT NULL
");
while ($row = $map_query->fetch_assoc()) {
    $map_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GreenAtlas</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <!-- Leaflet MarkerCluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css"/>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* GLOBAL */
        html, body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            font-family: Poppins, sans-serif;
            background: #0b0f14;
            animation: fadeInPage 0.8s ease;
        }

        @keyframes fadeInPage {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        /* NAVBAR */
        .navbar {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 95%;
            max-width: 1400px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            z-index: 1000;
        }

        .navbar div {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .navbar a {
            padding: 10px 18px;
            border-radius: 25px;
            font-size: 14px;
            text-decoration: none;
            color: #e6fff2;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            transition: 0.3s;
        }

        .navbar a:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 15px rgba(82,183,136,0.4);
        }

        .navbar a.register-btn {
            background: linear-gradient(90deg, #52b788, #40916c);
            color: white;
            font-weight: 600;
        }

        /* LOGO */
        .logo {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(90deg, #00ffcc, #52b788, #b7e4c7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* HERO */
        .hero {
            height: 100vh;
            position: relative;
            overflow: hidden;
        }

        .bg-video {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.85) contrast(1.15) saturate(1.2);
        }

        .hero-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 2;
        }

        .hero-content h1 {
            font-size: 60px;
            color: white;
            text-shadow: 0 0 30px rgba(0,255,150,0.7);
        }

        .hero-content p {
            font-size: 18px;
            color: #b7e4c7;
        }


    /* HERO STATS */
.hero-stats {
    display: flex;
    gap: 30px;
    justify-content: center;
    margin-top: 30px;
    flex-wrap: wrap;
}

.hero-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 14px;
    padding: 14px 24px;
    min-width: 90px;
}

.stat-num {
    font-size: 28px;
    font-weight: 700;
    color: #fff;
    line-height: 1;
}

.stat-lbl {
    font-size: 11px;
    color: #95d5b2;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-top: 4px;
}
        /* BUTTONS */
        .btn {
            padding: 12px 28px;
            margin: 10px;
            border-radius: 30px;
            text-decoration: none;
        }

        .btn.primary {
            background: linear-gradient(90deg, #52b788, #40916c);
            color: white;
        }

        .btn.secondary {
            border: 1px solid #b7e4c7;
            color: #b7e4c7;
        }

        /* MAP */
        .map-wrapper {
            position: relative;
            width: 100%;
            height: 500px;
            margin: 40px 0;
            border-radius: 20px;
            overflow: hidden;
        }

        #map {
            width: 100%;
            height: 100%;
        }

        .map-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(0,0,0,0.4);
            color: #b7e4c7;
            z-index: 10;
            cursor: pointer;
            transition: opacity 0.5s ease;
            font-size: 18px;
        }

        .map-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        /* MARKERS */
        .animal-marker {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
        }

        .animal-marker img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .glow-green { box-shadow: 0 0 10px 3px rgba(82,183,136,0.7); }
        .glow-red   { box-shadow: 0 0 10px 3px rgba(220,50,50,0.7);  }
        .glow-orange{ box-shadow: 0 0 10px 3px rgba(255,140,0,0.7);  }

        /* GRID */
        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px;
        }

        /* CARD */
        .card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            overflow: hidden;
            text-decoration: none;
            color: #e6fff2;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .card-content {
            padding: 12px;
        }

        .card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 0 25px rgba(82,183,136,0.3);
        }

        .threat-red    { color: #ff4d4d; font-weight: 600; }
        .threat-orange { color: #ffa500; font-weight: 600; }
        .threat-green  { color: #52b788; font-weight: 600; }

        /* SECTION */
        .explore-section {
            padding: 40px 20px;
            color: #e6fff2;
        }

        .section-title {
            text-align: center;
            font-size: 32px;
            color: #b7e4c7;
            margin-bottom: 20px;
        }

        .explore-section form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 30px;
        }

        .explore-section input,
        .explore-section select,
        .explore-section button {
            padding: 10px 16px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.05);
            color: #e6fff2;
            font-size: 14px;
        }

        .explore-section button {
            background: linear-gradient(90deg, #52b788, #40916c);
            color: white;
            cursor: pointer;
            border: none;
        }

        /* FOOTER */
        .footer {
            background: #0b0f14;
            color: #b7e4c7;
            padding: 50px 20px 20px;
            margin-top: 60px;
            position: relative;
        }

        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .footer a {
            color: #b7e4c7;
            text-decoration: none;
            display: block;
            margin-bottom: 6px;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 20px;
            opacity: 0.6;
        }

        .social-icons a {
            margin-right: 12px;
            font-size: 20px;
        }

        /* WAVES */
        .waves {
            position: absolute;
            top: -60px;
            width: 100%;
            height: 60px;
            overflow: hidden;
        }

        .wave {
            width: 200%;
            height: 100%;
            background: url('https://i.ibb.co/wQZVxxk/wave.png') repeat-x;
            animation: waveMove 6s linear infinite;
            opacity: 0.2;
        }

        @keyframes waveMove {
            from { background-position-x: 0; }
            to   { background-position-x: 1000px; }
        }

        .hero-tagline {
            font-size: 14px;
            letter-spacing: 4px;
            color: #95d5b2;
            margin-top: 10px;
            opacity: 0.8;
        }

        * { box-sizing: border-box; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <h3 class="logo">GreenAtlas 🌿</h3>
    <div>
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <a href="login.php"><i class="fas fa-user"></i> Login</a>
        <a href="register.php" class="register-btn"><i class="fas fa-user-plus"></i> Register</a>
        <a href="quiz.php"><i class="fas fa-question-circle"></i> Quiz</a>
    </div>
</div>

<!-- HERO -->
<div class="hero">
    <video class="bg-video" autoplay muted loop>
        <source src="uploads/assets/hero-video.mp4" type="video/mp4">
    </video>

    <div class="hero-content">
        <h1>Green <span>Atlas</span></h1>
        <p>Explore Wildlife Like Never Before</p>
        <p class="hero-tagline">Discover • Explore • Protect Wildlife 🌿</p>
    
        <div class="hero-buttons">
    <a href="#explore" class="btn primary">Explore Now</a>
    <a href="#" class="btn secondary">Learn More</a>
</div>

<div class="hero-stats">
    <div class="hero-stat">
        <span class="stat-num"><?php echo $total_species; ?></span>
        <span class="stat-lbl">Species</span>
    </div>
    <div class="hero-stat">
        <span class="stat-num" style="color:#ff4d4d"><?php echo $endangered_count; ?></span>
        <span class="stat-lbl">Endangered</span>
    </div>
    <div class="hero-stat">
        <span class="stat-num" style="color:#f59e0b"><?php echo $vulnerable_count; ?></span>
        <span class="stat-lbl">Vulnerable</span>
    </div>
    <div class="hero-stat">
        <span class="stat-num" style="color:#52b788"><?php echo $types_count; ?></span>
        <span class="stat-lbl">Species Types</span>
    </div>
</div>
    </div>
</div>

<!-- MAP -->
<div class="map-wrapper">
    <div id="map"></div>
    <div class="map-overlay" onclick="enableMap()">
        Click to Explore Wildlife Map 🌍
    </div>
</div>

<!-- EXPLORE SECTION -->
<section id="explore" class="explore-section">
    <h2 class="section-title">Explore Species 🐾</h2>

    <form method="GET">
        <input type="text" name="search" placeholder="Search..."
               value="<?php echo htmlspecialchars($search); ?>">

        <select name="type">
    <option value="">All Types</option>
    <option value="Mammal"    <?php if($type_filter === 'Mammal')    echo 'selected'; ?>>Mammal</option>
    <option value="Bird"      <?php if($type_filter === 'Bird')      echo 'selected'; ?>>Bird</option>
    <option value="Reptile"   <?php if($type_filter === 'Reptile')   echo 'selected'; ?>>Reptile</option>
    <option value="Amphibian" <?php if($type_filter === 'Amphibian') echo 'selected'; ?>>Amphibian</option>
    <option value="Marine"    <?php if($type_filter === 'Marine')    echo 'selected'; ?>>Marine</option>
    <option value="Fish"      <?php if($type_filter === 'Fish')      echo 'selected'; ?>>Fish</option>
</select>

<select name="threat">
    <option value="">All Threats</option>
    <option value="Endangered"      <?php if($threat_filter === 'Endangered')      echo 'selected'; ?>>Endangered</option>
    <option value="Vulnerable"      <?php if($threat_filter === 'Vulnerable')      echo 'selected'; ?>>Vulnerable</option>
    <option value="Near Threatened" <?php if($threat_filter === 'Near Threatened') echo 'selected'; ?>>Near Threatened</option>
    <option value="Low"             <?php if($threat_filter === 'Low')             echo 'selected'; ?>>Low</option>
</select>

        <button type="submit">Apply</button>
    </form>

    <div class="container">
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php
                if ($row['threat_level'] === 'Endangered')     $threatClass = 'threat-red';
                elseif ($row['threat_level'] === 'Vulnerable') $threatClass = 'threat-orange';
                else                                           $threatClass = 'threat-green';
            ?>
            <a href="species_detail.php?id=<?php echo (int)$row['species_id']; ?>" class="card">
                <img src="uploads/<?php echo htmlspecialchars($row['image_path'] ?: 'default.jpg'); ?>"
                     alt="<?php echo htmlspecialchars($row['species_name']); ?>">
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($row['species_name']); ?></h3>
                    <p><?php echo htmlspecialchars($row['species_type']); ?></p>
                    <span class="<?php echo $threatClass; ?>">
                        <?php echo htmlspecialchars($row['threat_level']); ?>
                    </span>
                </div>
            </a>
        <?php endwhile; ?>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="waves">
        <div class="wave" id="wave1"></div>
        <div class="wave" id="wave2"></div>
        <div class="wave" id="wave3"></div>
        <div class="wave" id="wave4"></div>
    </div>

    <div class="footer-container">
        <div class="footer-brand">
            <h2>GreenAtlas 🌿</h2>
            <p>Exploring and protecting wildlife across the globe.</p>
        </div>

        <div class="footer-links">
            <h3>Quick Links</h3>
            <a href="#">Home</a>
            <a href="#explore">Explore</a>
            <a href="login.php">Login</a>
            <a href="views/auth/register.php">Register</a>
        </div>

        <div class="footer-social">
            <h3>Connect</h3>
            <div class="social-icons">
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-github"></i></a>
                <a href="#"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> GreenAtlas. All rights reserved.</p>
    </div>
</footer>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

<script>
    function enableMap() {
        document.querySelector('.map-overlay').classList.add('hidden');
        map.scrollWheelZoom.enable();
    }

    var map = L.map('map', {
        scrollWheelZoom: false,
        dragging: true,
        tap: true,
        doubleClickZoom: true
    }).setView([20.5937, 78.9629], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'Map data &copy; OpenStreetMap contributors'
    }).addTo(map);

    var markers = L.markerClusterGroup({
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        spiderfyOnMaxZoom: true,
        iconCreateFunction: function(cluster) {
            var childMarkers = cluster.getAllChildMarkers();
            var html = childMarkers[0].options.icon.options.html;
            return L.divIcon({
                html: `<div style="position:relative;display:inline-block;">
                    ${html}
                    <span style="position:absolute;bottom:-5px;right:-5px;background:#000;color:#fff;padding:3px 6px;border-radius:10px;font-size:10px;">${childMarkers.length}</span>
                </div>`,
                className: "custom-cluster",
                iconSize: L.point(60, 60)
            });
        }
    });

    var data = <?php echo json_encode($map_data); ?>;

    data.forEach(function(row) {
        var imagePath = (row.image_path && row.image_path !== "")
            ? "uploads/" + row.image_path
            : "uploads/default.jpg";

        var glowClass = "glow-green";
        if (row.threat_level === "Endangered")      glowClass = "glow-red";
        else if (row.threat_level === "Vulnerable") glowClass = "glow-orange";

        var customIcon = L.divIcon({
            html: `<div class="animal-marker ${glowClass}">
                <img src="${imagePath}" onerror="this.src='uploads/default.jpg'">
            </div>`,
            className: "",
            iconSize: [40, 40]
        });

        var marker = L.marker([row.latitude, row.longitude], { icon: customIcon })
            .bindPopup(`
                <div style="font-family:Poppins,sans-serif;width:180px;text-align:center;">
                    <img src="${imagePath}"
                         onerror="this.src='uploads/default.jpg'"
                         style="width:100%;height:110px;object-fit:cover;border-radius:8px;margin-bottom:8px;">
                    <div style="font-weight:600;font-size:14px;margin-bottom:2px;">${row.species_name}</div>
                    <div style="font-size:12px;color:#666;margin-bottom:6px;">${row.species_type}</div>
                    <div style="
                        display:inline-block;font-size:11px;font-weight:600;
                        padding:3px 10px;border-radius:12px;margin-bottom:10px;
                        background:${row.threat_level==='Endangered'?'#ff4d4d22':row.threat_level==='Vulnerable'?'#f59e0b22':'#22c55e22'};
                        color:${row.threat_level==='Endangered'?'#ff4d4d':row.threat_level==='Vulnerable'?'#f59e0b':'#22c55e'};
                    ">${row.threat_level}</div><br>
                    <a href="species_detail.php?id=${row.species_id}" style="
                        display:inline-block;padding:7px 18px;
                        background:linear-gradient(90deg,#52b788,#40916c);
                        color:white;text-decoration:none;border-radius:20px;
                        font-size:12px;font-weight:600;">
                        View Details →
                    </a>
                </div>
            `, { maxWidth: 210 });

        markers.addLayer(marker);
    });

    map.addLayer(markers);
</script>

</body>
</html>