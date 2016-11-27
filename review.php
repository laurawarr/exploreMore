<?php

$db = new PDO('mysql:host=localhost;dbname=warrla_campsites;charset=utf8','warrla_warrla','ZcVukVOolaW3he3G');
$db -> setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db -> setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

if( isset($_GET['site']) ) {
	$site = $_GET['site'];
} else {
	$site = 0;
};

$sql = "SELECT site_name FROM sites WHERE site_id = :site";
$query = $db -> prepare( $sql );
$query -> execute( [':site' => $site]);
$data = $query -> fetch();

$sql7 = "SELECT * FROM reviews
INNER JOIN sites
ON (sites.site_id = reviews.site)";
$result7 = $db -> query($sql7);
$reviewData = $result7 -> fetchAll();

echo "<a class='back' href='index.php'><b>BACK TO SEARCH</b></a>";

echo "<div id='review-results'>";
echo "<h2>What others thought of ".$data['site_name']."...</h2>";
$noReviews = true;

foreach ($reviewData as $review){
	if ($review['site'] == $site) {
		echo "<p>".$review['review']."</p>";
		$noReviews = false;
	};
}; //end foreach review
if ($noReviews) echo "No reviews yet...";
echo "</div>";

echo "<a class='share submit review-button' href='index.php#footer'>Share your experience!</a>";

?>