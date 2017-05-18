<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>How to Look at Artist Networks</title>
		<script type='text/javascript' src='javascript/jquery-2.1.4.min.js'></script>
		<script type='text/javascript' src='javascript/jquery.autocomplete.min.js'></script>
		<script type='text/javascript' src='javascript/know.js'></script>
		
		<link href='https://fonts.googleapis.com/css?family=Anonymous+Pro|Patrick+Hand+SC|Patrick+Hand|Inconsolata|Rancho' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
		
		<link href="style.css" media="all" rel="stylesheet">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">

</head>
<body>
<div class="container">
	<div class="row">
		

<?php
if ($_POST['submit_person']) {
	$to = 'angeliquewaller@gmail.com';
	$subj = 'Request from howtolook';
	$msg = $_POST['submit_person'] . ' ' . $_POST['submit_email'];
	mail($to, $subj, $msg);
	$results = <<<EOH
		<h1>Thanks for submitting.<br />You are invited to try another artist.</h1>
EOH;
} else if ($_GET['q']) {
	include_once('knowgraph_lib.php');
	$conn = getDB();
	$sql = <<<SQL
		SELECT *
		FROM `people`
		WHERE `q` = "{$_GET[q]}"
		AND paths LIKE '%\"1651\"%' AND paths LIKE '%\"81891\"%'
SQL;

	require_once("lib/Mustache/Autoloader.php");
	Mustache_Autoloader::register();
	$mustache_engine = new Mustache_Engine(array(
		'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/templ'),
	));

	$pathsarr = array('Marcel Duchamp' => 81891, 'Pablo Picasso' => 1651);
	if ($result = $conn->query($sql)) {
		$r = $result->fetch_assoc();
		$name = $r['name'] ? $r['name'] : $_GET['q'];
		if ($r['paths']) {
			$paths = json_decode($r['paths']);
			foreach ($pathsarr as $k => $v) {
				$p = new stdClass();
				$p->artist = $k;
				$html .= "<ul>\n\t<li>\n";
				foreach ($paths->{$v} as $solution) {
					array_walk($solution, function(&$n) {
						global $conn;
						$sql = "SELECT * FROM people WHERE id=$n";
						if ($result2 = $conn->query($sql)) {
							$r2 = $result2->fetch_assoc();
							$n = new stdClass();
							$n->name = $r2['name'];
							$n->q = urlencode($r2['q']);
						}
					}); 
					$p->paths[] = $solution;
				}
				$people[] = $p;
				$count[$k] = count($p->paths[0]);
			}
			if ($count['Marcel Duchamp'] > $count['Pablo Picasso']) {
				$headline = "$name is more closely connected to Picasso.";
			} else if ($count['Pablo Picasso'] > $count['Marcel Duchamp']) {
				$headline = "$name is more closely connected to Duchamp.";
			} else {
				$headline = "$name is equally close to Picasso and Duchamp.";
			}
			$results = $mustache_engine->render('hops', $people);
		} else {
			$results = <<<EOH
			<h1>No results were found. Please see <a href="#getyourname">instructions</a> for getting into the Knowledge Graph and/or submit the artist name for consideration.</h1>
			<h5>The name you entered:</h5>
			<form action="" method="post">
				<input type="text" value="$name" name="submit_person">
				<input type="text" name="submit_email" placeholder="Your Email">
				<input type="submit" value="submit artist">
			</form>
EOH;
		}
	}
}

?>
		<div class="col-sm-4"><h1 class="titletext">How to Look at Artist Networks</h1></div>
		<div class="col-sm-5"><h1></h1><strong>Enter a contemporary artist in the search engine below.  The search engine curator will determine if the artist you entered is more closely connected to Picasso or Duchamp.</strong><br/></div>
		<div class="infobutton col-sm-2 text-right small"><h1></h1><a href="#info"><img src="imgs/infobutton.png" width="164" alt="more infomation"></a></div>
	</div>
	<div class="row">
		
		<div class="col-sm-5">
		

			<form action="" method="post" id="artist">
				<input type="text" value="<?php echo $name ?>" id="autocomplete">
				<input type="hidden" name="q" id="q" value="">
				<input type="hidden" name="savedq" id="savedq" value="<?php echo $_GET['q']?>">
				<input type="submit" value="submit">
			</form>
			
			<?php if($headline){
			print "<div class='text-center thumbnail'><img src='imgs/duchampwrong.png' width='250' alt='Duchamp was wrong'></div>";
			print "<h1>{$headline}</h1><small>*All results are based on Google data.</small>";
			}?>
			
			<div class="hops">
			<?php
			print $results;
			?>
			
			</div>
		
		</div>	
		<div class="col-sm-7 thumbnail">
		<img src="imgs/tree2.png" alt="Art World SEO tree">
			
		</div>
		</div>
		<?php if($headline){?>
		<div class="row" style="border-top:2px solid #000; margin-top:40px;">
		<?php }else{ ?>
		<div class="row" style="border-top:2px solid #000; margin-top:300px;">
		<?php } ?>
		<div class="col-sm-8">
		<a name="getyourname"></a>
		<h1>How to get your name in the Google Knowledge Graph.</h1>
		</div>
		<div class="row">
		<div class="col-sm-12 text-center thumbnail">
		<img src="imgs/sitemap.png"></div>
		</div>
		</div>	
		<div class="row" style="border-top:2px solid #000; margin-top:40px;border-bottom:2px solid #000; margin-bottom:40px;padding-bottom:40px;">
		<div class="col-sm-3" style="padding-top:24px;"><h4>Credits</h4>
		<p><strong>Concept/Design</strong><br />
		Angie Waller<br />
		<a href="https://twitter.com/angiewaller">@angiewaller</A><br/>
		<a href="http://www.angiewaller.com">angiewaller.com</a></p>
		<p><strong>Data scraping, programming</strong><br />
		Jonathan Butterick<br />
		<a href="http://www.grundig9000.com">grundig9000.com</a></p>
		<p>&nbsp;</p>
		<a href="https://www.google.com/?gws_rd=ssl#q=angie+waller" target="_blank" 
 onclick="window.open('https://www.google.com/?gws_rd=ssl#q=pablo+picasso'); 
window.open('https://www.google.com/?gws_rd=ssl#q=michelangelo');window.open('https://www.google.com/?gws_rd=ssl#q=vincent+van+gogh');window.open('https://www.google.com/?gws_rd=ssl#q=chris+burden');window.open('https://www.google.com/?gws_rd=ssl#q=leonardo+da+vinci');window.open('https://www.google.com/?gws_rd=ssl#q=rembrandt'); "><img src="imgs/secret_button.png"></a>
		<p>&nbsp;</p>
		<p>How to Look at Artist Networks" is a 2015 commission of New Radio and Performing Arts, Inc. for its <a href="http://www.turbulence.org">Turbulence.org</a> website. It was made possible with funds from the Jerome Foundation.
		<p>&nbsp;</p>
		</div>
		<div class="col-sm-8" style="padding-top:10px;">
		<a name="info"></a>
		<h2>Background</h2>
		<p>Art is presented, sold, and curated on the Internet. Contemporary artists are ranked, meta-tagged and algorithmically connected to other artists. The creation of the artistic canon, long considered the purview of academies and art historians, has long been complicated by the art market, and is now being overridden in the Information Age. Artists are reduced to keywords that point to other artists’ keywords.</p>

<p>In 1976, before algorithms grouped artists for a mass audience, Chris Burden explored the effect of artist as keyword in a commercial. Broadcasted on late night television, it consisted of a plain screen with the names “Leonardo da Vinci, Michelangelo, Rembrandt, Vincent van Gogh, Picasso, Chris Burden” displayed in succession with the soundtrack of Burden’s voice reading the names aloud. Television did not have to compete for our attention the way it does now. His late night commercial reached thousands of homes.  If broadcast enough times, Burden reckoned he would have become a household name on par with the other artists mentioned.<sup><a href="#notes">1</a></sup></p>


<p>In 2012, Google developed an innovation called Knowledge Graph. Most users have witnessed this feature without realizing it. It’s a special box that comes up when one searches for famous people and places on Google. Google’s Knowledge Graph tries to determine if the search keyword is not only a word but a person, place or thing. It might reveal the height of the Eiffel Tower or that Kim Kardashian is married to Kanye West.<sup><a href="#notes">2</a></sup> If keyword A is recognized by Knowledge Graph as the name of an “artist,” it will retrieve other “artists” associated with keyword A. Like Chris Burden’s commercial, Knowledge Graph suggests associated names with little context.</p>

<p class="thumbnail">
<img src="imgs/leo_knowledge_graph_2.png" style="border:1px solid #eee;"><p>&nbsp;</p>
</p>
<p>Chris Burden’s commercial might be a stylistic outlier in his oeuvre. His career was started in graduate school when for his master’s thesis he spent five consecutive days curled up in his 2' x 2' x 3' locker without leaving. Reflecting on those early stages in his career, Burden recounted that artists had two paths to choose – Picasso or Duchamp. In case the connection between his body endurance performances and conceptual readymades isn’t clear, Burden chose Duchamp.<sup><a href="#notes">3</a></sup></p> 

<p>Today, an artist’s influences seem more like a tag cloud that changes in density depending on the season. We have settled into appropriation, borrowing freely from multiple artists before us with the hopes that the right mix will make something seem original. In the current context, it’s hard to imagine a patriarchal artist family tree  in the style of Ad Reinhardt updated with Duchamp and Picasso sprouting separate trunks. Our current era of tag clouds suggests different vegetation, perhaps fungus or moss.</p>

<p>Algorithms work in binaries of black and white. If art is being described with data, it is difficult to define shades of gray. Artsy, an online art market, has tried to address this issue by offering hundreds of tags for artwork that describe appearance or subject matter (e.g., race, eye contact, stolen moments). To deal with gray areas, art objects can be rated on a scale 1 to 10 for each of these tags. Artsy’s goal is that this process might return meaningful search results that lead to sales. However, the Artsy experience is less like visiting a gallery and more like searching for clip art for a PowerPoint presentation: art is confusingly divorced from any historical context.</p>

<p>"How to Look at Artist Networks" illuminates the challenge of artist classification in the digital age by using Picasso and Duchamp as the central binary. In the current historical moment, fame may have muddied their distinct differences and rendered their names little more than a sampling of most recognized artists, similar to Burden’s commercial. But not too long ago, Picasso and Duchamp signified two distinct strains of practice. Pointing to them as the progenitors of all modern and post-modern artistic thought can introduce amusing, and hopefully enlightening, connections.</p>

<p>You are invited to search through 60,280 names and see if they are more closely connected to Duchamp or Picasso in the Google Knowledge Graph. The premise is similar to the popular meme “six degrees of Kevin Bacon.” Rather than movies, the connections are based on the “people also searched for” listings in Google search results. To keep the data manageable, the outer limit was set at five degrees. If you let the “How to Look” auto-fill do its thing, you will see a lot of unlikely names. Personally I was surprised to see Sarah Palin, Matthew McConaughey and Meat Loaf, and be faced with contemplating the connection between their artistic practices and those of Picasso and Duchamp. I hope you will discover some surprises of your own; you may even be one of the data points included.<p>
<a name="notes"></a>
<h6>Notes</h6>
<small>
<ol>

<li>MOCAtv: Chris Burden's Late Night Commercials, Feb. 28, 2013, https://youtu.be/s8QrrExMUvQ</li>
<li>Sullivan, Danny. "Google Launches Knowledge Graph To Provide Answers, Not Just Links." Search Engine Land. N.p., 16 May 2012.
http://searchengineland.com/google-launches-knowledge-graph-121585</li>
<li>Schjeldahl, Peter. "Chris Burden and the Limits of Art." The New Yorker 14 (2007).</li>
</ol>
</small>
		</div>
		</div>	
		
	

</div>

<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>

<!-- Latest compiled JavaScript -->
<script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
</body>
</html>
