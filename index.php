<?php session_start() ?>
<?php
	//create a PDO (PHP Data Object)
	//specifies db type, host, db name, char set, username and password
$db = new PDO('mysql:host=localhost;dbname=warrla_campsites;charset=utf8','warrla_warrla','ZcVukVOolaW3he3G');
// $db = new PDO('mysql:host=localhost;dbname=campsites;charset=utf8','root','root');
	//set error mode, which allows errors to be thrown, rather than silently ignored
$db -> setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db -> setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

	//dump out connection info 
	// var_dump( $db );

$sql = "SELECT * FROM sites ORDER BY site_name";
$result = $db -> query($sql);
$data = $result -> fetchAll();
	//var_dump($data);

$sql2 = "SELECT * FROM trails";
$result2 = $db -> query($sql2);
$trailData = $result2 -> fetchAll();
	//var_dump($trailData);

$sql3 = "SELECT * FROM features";
$result3 = $db -> query($sql3);
$featData = $result3 -> fetchAll();

$sql4 = "SELECT trail_id, trail_name, site_name, site_id FROM `trails_to_sites` 
INNER JOIN trails
ON (trails.trail_id = trails_to_sites.trail)
INNER JOIN sites
ON (sites.site_id = trails_to_sites.site)
ORDER BY trail_id, site_name";
$result4 = $db -> query($sql4);
$trailToSiteData = $result4 -> fetchAll();
	//var_dump($trailToSiteData);

$sql6 = "SELECT feat_id, feat_name, site_name, site_id FROM `features_to_sites`
INNER JOIN features
ON (features.feat_id = features_to_sites.feature)
INNER JOIN sites
ON (sites.site_id = features_to_sites.site)
ORDER BY feat_id, site_name";
$result6 = $db -> query($sql6);
$featToSiteData = $result6 -> fetchAll();
	//var_dump($trailToSiteData);

$sql7 = "SELECT * FROM reviews
INNER JOIN sites
ON (sites.site_id = reviews.site)";
$result7 = $db -> query($sql7);
$reviewData = $result7 -> fetchAll();

$reviewError = '';
$valid = false;
$reviewed = false;

//DEFINES MAIN CATEGORY SELECTED IN HEADER
	if (isset($_POST["select"])){
		//defines the session variable so selected option is not lost on second POST
		$select = $_SESSION["select"] = $_POST["select"];
	} else if (isset($_SESSION["select"])){
		$select = $_SESSION["select"];
	};


?>
<?php include('header.php') ?>	

		<div id="content-main" class="row">
			<form action="index.php#content" id="filter" class="column" method="POST">
				<input id="searchBar" name="searchBar" type="text" placeholder="ex. Ramona">
				</input>
				<select id="trailBar" name="filterTrail">
					<option selected disabled>Trail Name</option>
					<?php 
						foreach($trailData as $trail){
							echo "<option value=".$trail['trail_id'].">".$trail['trail_name']."</option>";
						}; //end foreach 
					?>
				</select>
				<div id="checkboxes">
					<?php
						foreach ($featData as $feature) {
							$f = str_replace(' ', '', $feature['feat_name']);
							echo "<input type='checkbox' name=".$f." value=".$f.">".$feature['feat_name']."</input><br/>";
						};
					?>
				</div>
				<input class="submit" type="submit" value="FILTER RESULTS">

			</form>
			<div id="results" class="column">
				<?php 
					if ($_SERVER["REQUEST_METHOD"] == "POST"){

					///// REVIEW SANITIZE AND INSERT ///
						// Checks if review data has been submitted
						if(isset($_POST['reviewSite'])) {
							$reviewSite = $_POST['reviewSite'];
							$reviewed = true;
						} else {
							 $reviewSite = '';
						};
						if(isset($_POST['review'])) {
							$reviewText = $_POST['review'];
							$reviewed = true;
						} else {
							 $reviewText = '';
						};

						// Checks if input site is a valid site
						$sql = "SELECT * FROM sites WHERE site_name LIKE :reviewSite";
						$query = $db -> prepare( $sql );
						$query -> execute( [':reviewSite' => "%".$reviewSite."%"]);
						$reviewSearch = $query -> fetchAll();

						// Checks if review with same text is already in database
						$sql = "SELECT * FROM reviews WHERE review LIKE :reviewText";
						$query = $db -> prepare( $sql );
						$query -> execute( [':reviewText' => "%".$reviewText."%"]);
						$reviewDuplicate = $query -> fetchAll();

						// Once review is submitted, checks if values are empty and returns error if any are
						if ($reviewed){
							if (empty($reviewSearch)){
								$reviewError = "Site name doesn't match an existing site.";
							} else if (count($reviewSearch) > 1) {
								$reviewError = "Please give a more specific site name";
							} else if ($reviewText == ''){
								$reviewError = "Review cannot be empty";
							} else if (empty($reviewDuplicate)){
								$reviewSite = $reviewSearch[0]['site_id'];
								$valid = true;
							};
						};

						// Queries SQL if input review is "valid"
						if ($valid){
							try{$sql2 = "INSERT INTO `reviews` (`review_id`, `review`, `site`) VALUES (NULL, '$reviewText', '$reviewSite')";
								$query = $db -> prepare($sql2);
								$query -> execute();
							} catch(PDOException $ex){
								echo "SITE: ".$reviewSite;
								echo "TEXT: ".$reviewText;
								echo "Error Occured: ";
								echo $ex -> getMessage();
							};
						};

					///// FILTER  ///
						// Checks if searchBar has been entered, queries searchData if it is
						if (!empty($_POST['searchBar'])) {
							$searchBar = $_POST['searchBar'];
							$sql3 = "SELECT * FROM sites WHERE site_name LIKE :searchBar";
							$query = $db -> prepare( $sql3 );
							$query -> execute( array(':searchBar' => "%".$searchBar."%"));
							$searchData = $query -> fetchAll();
						} else {
							$searchBar = null;
						};

						// Checks if trail has been defined 
						$filterTrail = null;
						if (isset($_POST['filterTrail'])) $filterTrail = $_POST['filterTrail'];

						// Returns list of condensed feature names if defined (ie. Water Access -> WaterAccess)
						$featFilterList = [];
						$featFiltered = false;
						foreach ($featData as $f){
							$f_name = str_replace(' ', '', $f['feat_name']);
							// if feature is defined (checked) then it's ID is added to list 
							if (isset($_POST[$f_name])) {
								$featFiltered = true;
								array_push($featFilterList, $_POST[$f_name]);
							}; //end it
						}; //end foreach


					///// FUNCTIONS ///
						//Takes in an empty array and a site, resulting array contains the site's trails //returns id
							function createTrailList($list, $site){
								global $trailToSiteData;
								foreach($trailToSiteData as $trail){
									//$f_name = str_replace(' ', '', $trail['trail_name']);
									if (($trail['site_id'] == $site['site_id']) && !array_search($trail['site_id'], $list)){
										
										array_push($list, $trail['trail_id']);
									};
								}; //end foreach trailData
								return $list;
							}; //end createTrailList

						//Takes in an empty array and a site, resulting array contains the site's features //returns name
							function createFeatList($list, $site){
								global $featToSiteData;
								foreach($featToSiteData as $feat){
									$f_name = str_replace(' ', '', $feat['feat_name']);
									if (($feat['site_id'] == $site['site_id']) && !array_search($f_name, $list)){
										
										array_push($list, $f_name);
									};
								}; //end foreach featData
								return $list;
							}; //end createFeatList

						// Compare two lists, returns true if all $list1 elements are in $list2, otherwise returns false
							function includesList($list1, $list2){
								$match = 1;
								foreach($list1 as $l1){
									$match = array_search($l1, $list2);
									if (!is_numeric($match)) return false;
								};
								return true;
							};

					///// RESULTS LISTED BY NAME ///
						echo "<h3><i>Searching sites by: ".ucfirst($select)."</i></h3>";
						// if search bar is used, page defults to sorting by name 
						(empty($searchData)) ? $searchData = $data : $select = "name";
						if (isset($select) && $select == "name"){
							foreach ($searchData as $site){

								//creates list of features for specified $site
								$siteFeatList = [];
								$siteFeatList = createFeatList($siteFeatList, $site);

								//creates list of trails for specified $site
								$siteTrailList = [];
								$siteTrailList = createTrailList($siteTrailList, $site);

								// if all features in $featFilterList are not contained in $siteFeatList (and results have been filtered by feature) return nothing
								// if $filterTrail is not in $siteTrailList (and $filterTrail has been defined) return nothing
								if ((!includesList($featFilterList, $siteFeatList) && $featFiltered)||(isset($filterTrail) && !includesList([$filterTrail], $siteTrailList))){
								} else {

									echo "<div class='siteHeader'>";
									echo "<a href='index.review.php?site=".$site['site_id']."#content'><h2>".$site['site_name']."</h2></a>";

									echo "<div id='feature-icons'>";
									foreach($siteFeatList as $f){
										echo "<img class='icon' src='_images/".$f.".svg'/>";
									};
									echo "</div></div>";

								}; //end if/else featFiltered

							}; //end foreach site

					///// RESULTS LISTED BY TRAIL ///
						} else if (isset($select) && $select == "trail"){
							// $filterList = [];
							// $unfiltered = true;

							$trail = -1;
							//iterate over all trails
							foreach ($trailToSiteData as $t){
								$trail_id = $t['trail_id'];

								//creates list of features for specified site $t
								$siteFeatList = [];
								$siteFeatList = createFeatList($siteFeatList, $t);

								// if all features in $featFilterList are not contained in $siteFeatList (and results have been filtered by feature) return nothing
								if (!includesList($featFilterList, $siteFeatList) && $featFiltered){
								} else {

									//filter by specific trail name
									// skip trail if it is not the chosen trail (if trail has been selected) 
									if (isset($filterTrail) && ($trail_id != $filterTrail)){
									} else if ($trail != $trail_id){
										if ($trail > -1){
											echo "</div>";
										}
										echo "<div class='trail'>";
										echo "<h2>".$t['trail_name']."</h2>";

										echo "<div class='siteHeader'>";
										echo "<a href='index.review.php?site=".$t['site_id']."#content'><h3>".$t['site_name']."</h3></a>";

										//generates feature icons from $siteFeatList
										echo "<div id='feature-icons'>";
										foreach($siteFeatList as $f){
											echo "<img class='icon' src='_images/".$f.".svg'/>";
										};
										echo "</div></div>";

										$trail = $trail_id;

									} else {
										echo "<div class='siteHeader'>";
										echo "<a href='index.review.php?site=".$t['site_id']."#content'><h3>".$t['site_name']."</h3></a>";

										//generates feature icons from $siteFeatList
										echo "<div id='feature-icons'>";
										foreach($siteFeatList as $f){
											echo "<img class='icon' src='_images/".$f.".svg'/>";
										};
										echo "</div></div>";
									}; //end if
								}; // end if featFiltered
							}; //end for
							echo "</div>";

					///// RESULTS LISTED BY SITE FEATURES ///
						} else if (isset($select) && $select == "feature"){
							$feature = -1;
							//iterate over all features
							foreach ($featToSiteData as $f){
								$feat_id = $f['feat_id'];
								$f_name = str_replace(' ', '', $f['feat_name']);

								//creates list of features for specified site $t
								$siteFeatList = [];
								$siteFeatList = createFeatList($siteFeatList, $f);

								//creates list of trails for specified $site
								$siteTrailList = [];
								$siteTrailList = createTrailList($siteTrailList, $f);

								// skip feature if it is not in list and results have been filtered
								if (((false === array_search($f_name, $featFilterList)) && $featFiltered)||(isset($filterTrail) && !includesList([$filterTrail], $siteTrailList))){
								} else if ($feature != $feat_id){
									if ($feature > -1){
										echo "</div>";
									}
									echo "<div class='feat'>";
									echo "<h2>".$f['feat_name']."</h2>";

									echo "<div class='siteHeader'>";
									echo "<a href='index.review.php?site=".$f['site_id']."#content'><h3>".$f['site_name']."</h3></a>";

									//generates feature icons from $siteFeatList
									echo "<div id='feature-icons'>";
									foreach($siteFeatList as $feat){
										echo "<img class='icon' src='_images/".$feat.".svg'/>";
									};
									echo "</div></div>";
									$feature = $feat_id;
								} else {
									echo "<div class='siteHeader'>";
									echo "<a href='index.review.php?site=".$f['site_id']."#content'><h3>".$f['site_name']."</h3></a>";

									//generates feature icons from $siteFeatList
									echo "<div id='feature-icons'>";
									foreach($siteFeatList as $feat){
										echo "<img class='icon' src='_images/".$feat.".svg'/>";
									};
									echo "</div></div>";
								}; //end if
							}; //end foreach
							echo "</div>";
						} else {
							echo "Search did not return any results.";
						}

					}; // end if POST
				?>
			</div>
		</div>
	
		<div id="footer" class="column">
			<a id="backToTop" href="#"><h3>BACK TO SEARCH</h3></a>
			<form action="index.php#footer" class="column" method="POST">
				<h2>SHARE YOUR EXPERIENCE</h2>
				<span class="error"><?php echo $reviewError ?></span>
				<input type="text" name="reviewSite" id="reviewSite" placeholder="Site for review"></input>
				<textarea name="review" id="reviewText" placeholder="Share your experience (max 255 characters)"></textarea>
				<input type="submit" id="reviewSubmit" value="SUBMIT" />
			</form>
		</div>
	</div>
</div>
</body>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="_scripts/main.js"></script>
</html>