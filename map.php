<?php
include "config/database.php";
$result = $conn->query("SELECT * FROM species WHERE status='Approved'");
?>

<!DOCTYPE html>
<html>
<head>
<title>Species Map</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />

<style>
body {
    font-family: Poppins;
    text-align: center;
}

#map {
    height: 85vh;
    width: 100%;
}

.filters {
    margin: 15px;
}

button {
    padding: 8px 15px;
    margin: 5px;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    background: #2d6a4f;
    color: white;
}

button.active {
    background: #1b4332;
}

.marker-cluster-small {
    background-color: rgba(45, 106, 79, 0.6);
}
.marker-cluster-medium {
    background-color: rgba(27, 67, 50, 0.7);
}
.marker-cluster-large {
    background-color: rgba(8, 28, 21, 0.8);
}
</style>
</head>

<body>

<h2>🌍 Species Map</h2>

<div class="filters">
    <button onclick="filterSpecies('all', event)">All</button>
    <button onclick="filterSpecies('Mammal', event)">Mammal</button>
    <button onclick="filterSpecies('Bird', event)">Bird</button>
    <button onclick="filterSpecies('Reptile', event)">Reptile</button>
</div>

<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

<script>
window.addEventListener('load', function () {

    var map = L.map('map').setView([20.5937, 78.9629], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    var markers = [];
    var clusterGroup = L.markerClusterGroup();

    <?php 
    while($row = $result->fetch_assoc()){ 
    if(!empty($row['latitude']) && !empty($row['longitude'])) {
    ?>

    var marker = L.marker([<?php echo $row['latitude']; ?>, <?php echo $row['longitude']; ?>])
    .bindPopup(`
        <b><?php echo htmlspecialchars($row['species_name']); ?></b><br>
        <i><?php echo htmlspecialchars($row['species_type']); ?></i><br>
        <img src="uploads/<?php echo !empty($row['image_path']) ? htmlspecialchars($row['image_path']) : 'default.jpg'; ?>" 
             width="100"
             onerror="this.src='uploads/default.jpg'"><br>
        <?php echo substr(htmlspecialchars($row['description']), 0, 100); ?>...
    `);

    marker.speciesType = "<?php echo $row['species_type']; ?>";

    markers.push(marker);
    clusterGroup.addLayer(marker);

    <?php 
    } 
    } 
    ?>

    function filterSpecies(type, event){
        if(event && event.target){
            document.querySelectorAll("button").forEach(btn => btn.classList.remove("active"));
            event.target.classList.add("active");
        }

        clusterGroup.clearLayers();

        markers.forEach(marker => {
            if(type === 'all' || marker.speciesType === type){
                clusterGroup.addLayer(marker);
            }
        });
    }

    map.addLayer(clusterGroup);
    filterSpecies('all');

});
</script>

</body>
</html>