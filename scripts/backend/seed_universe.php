<?php
$root = dirname(__DIR__, 2);
require_once $root . '/config.php';
$dbName = getenv('SGW_DB_NAME') ?: 'sgw';
$dbUser = getenv('SGW_DB_USER') ?: 'sgw';
$dbPass = getenv('SGW_DB_PASS') ?: 'sgwpass';
$mysqli = new mysqli(getenv('SGW_DB_HOST') ?: '127.0.0.1', $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) { fwrite(STDERR, "Universe seed connection failed: " . $mysqli->connect_error . "\n"); exit(1); }
$mysqli->set_charset('utf8mb4');

$seed = getenv('SGW_UNIVERSE_SEED') ?: 'SGW-ARK-PRIME-2026';
$name = getenv('SGW_UNIVERSE_NAME') ?: 'The Stargate Expanse';
$galaxies = 3;
$insertMeta = $mysqli->prepare('INSERT INTO universe_meta (seed,universe_name,galaxy_count) VALUES (?,?,?) ON DUPLICATE KEY UPDATE universe_name=VALUES(universe_name), galaxy_count=VALUES(galaxy_count)');
$insertMeta->bind_param('ssi', $seed, $name, $galaxies); $insertMeta->execute();
$meta = $mysqli->query("SELECT universe_id FROM universe_meta WHERE seed='" . $mysqli->real_escape_string($seed) . "' LIMIT 1")->fetch_assoc();
$universeId = (int)$meta['universe_id'];

$starClasses = [['M','red',22],['K','amber',45],['G','yellow',70],['F','white',90],['A','blue-white',120],['B','azure',180]];
$hazards = ['Radiation Storm','Pirate Corridor','Solar Flare Belt','Gravitic Shear','Cold Silence','No Known Hazard'];
$climates = ['Temperate','Arid','Frozen','Oceanic','Toxic','Volcanic','Barren','Verdant','Gas Giant'];
$biomes = ['Crystal Forest','Iron Desert','Floating Reefs','Glacial Shelf','Ashlands','Mycelial Plains','Storm Seas','Ancient Ruins','Radiant Canopy'];
$resources = ['Naquadah','Trinium','Aetherium','Cryonite','Helium-3','Dark Matter','Luminous Quartz','Titanium','Organic Catalysts'];
$lifeforms = ['None Detected','Nomadic Collectives','Silicon Swarm','Ancient Sentients','Fungal Network','Aquatic Architects','Machine Intelligences','Precursor Echoes'];
$atmospheres = ['Breathable','Thin','Dense','Corrosive','Cryogenic','Methane','No Atmosphere'];
$nameA = ['Astra','Veyra','Khar','Orison','Nyx','Talos','Eidolon','Cinder','Luma','Sable','Riven','Aurel'];
$nameB = ['Reach','Drift','Prime','Haven','Crown','Veil','Frontier','Arc','Gate','Field','Bastion','Exile'];

function pick(array $list, int $n): string { return $list[$n % count($list)]; }
function rng(string $seed, string $key, int $max): int { $h = hash('sha256', $seed . '|' . $key); return hexdec(substr($h, 0, 8)) % max(1, $max); }
function generatedName(string $seed, string $key, array $a, array $b): string { return $a[rng($seed,$key.'a',count($a))] . ' ' . $b[rng($seed,$key.'b',count($b))]; }

$starStmt = $mysqli->prepare('INSERT INTO universe_stars (universe_id,galaxy_no,system_no,star_name,star_class,spectral_color,luminosity,hazard,has_station) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE star_id=LAST_INSERT_ID(star_id), star_name=VALUES(star_name), star_class=VALUES(star_class), spectral_color=VALUES(spectral_color), luminosity=VALUES(luminosity), hazard=VALUES(hazard), has_station=VALUES(has_station)');
$bodyStmt = $mysqli->prepare('INSERT INTO universe_bodies (star_id,body_no,body_type,body_name,seed_code,climate,biome,resource_primary,resource_secondary,lifeform,hazard,atmosphere,richness,habitability,parent_body_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE body_id=LAST_INSERT_ID(body_id), body_name=VALUES(body_name)');
for ($g=1; $g <= $galaxies; $g++) {
  $systems = 5 + $g * 2;
  for ($s=1; $s <= $systems; $s++) {
    $key = "G{$g}-S{$s}"; $class = $starClasses[rng($seed,$key.'class',count($starClasses))];
    $starName = generatedName($seed,$key,$nameA,$nameB); $color=$class[1]; $lum=$class[2]+rng($seed,$key.'lum',80); $haz=pick($hazards,rng($seed,$key.'haz',count($hazards))); $station=(int)(rng($seed,$key.'station',100)>58);
    $starStmt->bind_param('iiisssisi', $universeId,$g,$s,$starName,$class[0],$color,$lum,$haz,$station); $starStmt->execute();
    $starId=(int)$mysqli->insert_id; if($starId<1){$starId=(int)$mysqli->query("SELECT star_id FROM universe_stars WHERE universe_id={$universeId} AND galaxy_no={$g} AND system_no={$s}")->fetch_assoc()['star_id'];}
    $planetCount=3+rng($seed,$key.'planets',5);
    for($p=1;$p<=$planetCount;$p++){
      $pkey="$key-P$p"; $cl=pick($climates,rng($seed,$pkey.'cl',count($climates))); $bi=pick($biomes,rng($seed,$pkey.'bi',count($biomes))); $r1=pick($resources,rng($seed,$pkey.'r1',count($resources))); $r2=pick($resources,rng($seed,$pkey.'r2',count($resources))); $life=pick($lifeforms,rng($seed,$pkey.'life',count($lifeforms))); $ph=pick($hazards,rng($seed,$pkey.'haz',count($hazards))); $atm=pick($atmospheres,rng($seed,$pkey.'atm',count($atmospheres))); $rich=25+rng($seed,$pkey.'rich',76); $habit=$life==='None Detected'?10+rng($seed,$pkey.'hab',35):35+rng($seed,$pkey.'hab',66); $bodyName=generatedName($seed,$pkey,$nameA,$nameB); $code=$seed.'-'.$pkey; $type='planet'; $parent=null; $bodyNo=$p;
      $bodyStmt->bind_param('iissssssssssiii', $starId,$bodyNo,$type,$bodyName,$code,$cl,$bi,$r1,$r2,$life,$ph,$atm,$rich,$habit,$parent); $bodyStmt->execute(); $planetId=(int)$mysqli->insert_id; if($planetId<1){$planetId=(int)$mysqli->query("SELECT body_id FROM universe_bodies WHERE seed_code='".$mysqli->real_escape_string($code)."'")->fetch_assoc()['body_id'];}
      $moons=rng($seed,$pkey.'moons',4); for($m=1;$m<=$moons;$m++){ $mkey="$pkey-M$m"; $mname=generatedName($seed,$mkey,$nameA,$nameB).' Moon'; $mcode=$seed.'-'.$mkey; $mcl=pick($climates,rng($seed,$mkey.'cl',count($climates))); $mbi=pick($biomes,rng($seed,$mkey.'bi',count($biomes))); $mr1=pick($resources,rng($seed,$mkey.'r1',count($resources))); $mr2=pick($resources,rng($seed,$mkey.'r2',count($resources))); $ml=pick($lifeforms,rng($seed,$mkey.'life',count($lifeforms))); $mh=pick($hazards,rng($seed,$mkey.'haz',count($hazards))); $ma=pick($atmospheres,rng($seed,$mkey.'atm',count($atmospheres))); $mrich=20+rng($seed,$mkey.'rich',81); $mhabit=10+rng($seed,$mkey.'hab',61); $mt='moon'; $mparent=$planetId; $bodyStmt->bind_param('iissssssssssiii',$starId,$m,$mt,$mname,$mcode,$mcl,$mbi,$mr1,$mr2,$ml,$mh,$ma,$mrich,$mhabit,$mparent); $bodyStmt->execute(); }
    }
    if($station){$skey="$key-ST"; $st='station'; $stationName=generatedName($seed,$skey,$nameA,$nameB).' Exchange'; $code=$seed.'-'.$skey; $cl='Artificial';$bi='Orbital Megastructure';$r1='Trade Goods';$r2='Fuel Cells';$life='Multi-species Port';$ph=pick($hazards,rng($seed,$skey.'haz',count($hazards)));$atm='Pressurized';$rich=90;$habit=100;$parent=null;$bodyNo=99; $bodyStmt->bind_param('iissssssssssiii',$starId,$bodyNo,$st,$stationName,$code,$cl,$bi,$r1,$r2,$life,$ph,$atm,$rich,$habit,$parent);$bodyStmt->execute();}
    if(rng($seed,$key.'anomaly',100)>72){$akey="$key-AN";$at='anomaly';$aname=generatedName($seed,$akey,$nameA,$nameB).' Anomaly';$code=$seed.'-'.$akey;$cl='Unknown';$bi='Reality Distortion';$r1='Unknown Matter';$r2='Ancient Data';$life='Precursor Echoes';$ph='Temporal Instability';$atm='None';$rich=100;$habit=0;$parent=null;$bodyNo=100;$bodyStmt->bind_param('iissssssssssiii',$starId,$bodyNo,$at,$aname,$code,$cl,$bi,$r1,$r2,$life,$ph,$atm,$rich,$habit,$parent);$bodyStmt->execute();}
  }
}
$classNames=['Asterian','Boreal','Cinder','Dune','Elysian','Feral','Gaian','Helian','Ionic','Jovian','Kryotic','Lacustrine','Magma','Nebular','Oceanic','Pelagic','Quarian','Radiant','Sylvan','Terran','Umbrian','Verdant','Windscar','Xenial','Yonder','Zenith'];
$subclasses=['Prime','Frontier','Cradle','Exotic','Colony','Ancient','Rift','Highland','Deep'];
$biomeFamilies=['Terran','Arid','Oceanic','Cryonic','Volcanic','Gas Giant','Fungal','Crystal','Synthetic','Void'];
$subBiomes=['Open Plains','Canopy Basin','Iron Dunes','Salt Flats','Abyssal Trench','Glacial Shelf','Ash Caldera','Storm Belt','Mycelial Fields','Crystal Forest','Ruined Megastructure','Floating Reefs','Radiant Wastes','Subsurface Ocean','Orbital Habitat'];
$taxonomyQ=$mysqli->query('SELECT body_id,seed_code,body_type FROM universe_bodies');
$taxonomyStmt=$mysqli->prepare('UPDATE universe_bodies SET class_code=?,class_name=?,subclass_code=?,subclass_name=?,biome_family=?,sub_biome=?,size_rating=? WHERE body_id=?');
if($taxonomyQ){while($body=$taxonomyQ->fetch_assoc()){
  $key=(string)$body['seed_code']; $classIndex=rng($seed,$key.'-class',26); $classCode=chr(65+$classIndex); $className=$classNames[$classIndex]; $subIndex=rng($seed,$key.'-subclass',count($subclasses)); $subCode=$classCode.($subIndex+1); $subName=$subclasses[$subIndex].' '.$className; $bio=$biomeFamilies[rng($seed,$key.'-biomefamily',count($biomeFamilies))]; $subBio=$subBiomes[rng($seed,$key.'-subbiome',count($subBiomes))]; $size=1+rng($seed,$key.'-size',9); $bodyId=(int)$body['body_id']; $taxonomyStmt->bind_param('ssssssii',$classCode,$className,$subCode,$subName,$bio,$subBio,$size,$bodyId); $taxonomyStmt->execute();
}}
$geologies=['Iron-rich tectonic plates','Crystal lattice mantle','Floating gas strata','Ancient engineered crust','Subsurface ocean shell','Volcanic rift networks','Frozen methane shelves','Fractured precursor ruins'];
$ecologies=['Dense megaflora and pollinators','Sparse extremophile colonies','Silicon-based swarm ecology','Oceanic macrofauna','Fungal communication web','No native ecology detected','Machine-assisted biosphere','Predatory twilight ecology'];
$climateProfiles=['Stable temperate seasons','Rapid thermal cycling','Permanent storm season','Cryogenic night zones','High-radiation daylight','Tidal climate locked to primary','Volcanic atmospheric pulses','Low-gravity drifting weather'];
$factions=['Unclaimed frontier','Stargate Survey Corps','Independent merchant clans','Hidden precursor remnant','Nomad ark flotilla','Pirate salvage corridor','Machine custodian network','Scientific quarantine zone'];
$settlements=['Survey Outpost','Frontier Colony','Research Enclave','Mining Complex','Orbital Habitat','Quarantine Perimeter','Trade Nexus','Sacred Ruin Site'];
$effects=['Mineral extraction +10%','Survey speed +8%','Power generation +12%','Research output +10%','Fleet repair +8%','Trade value +15%','Defensive readiness +12%','Population growth +9%'];
$intQ=$mysqli->query('SELECT body_id,seed_code,body_type,richness,habitability FROM universe_bodies');
$intStmt=$mysqli->prepare('UPDATE universe_bodies SET geology_profile=?,ecology_profile=?,climate_profile=?,atmosphere_profile=?,resource_profile=?,hazard_profile=?,faction_presence=?,settlement_class=?,technology_level=?,power_affinity=?,economy_value=?,survey_difficulty=?,gameplay_effect=? WHERE body_id=?');
$designStmt=$mysqli->prepare('INSERT INTO universe_designs (body_id,design_type,design_name,metal_cost,crystal_cost,deuterium_cost,power_draw,output_bonus,unlocked) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE design_name=VALUES(design_name), metal_cost=VALUES(metal_cost), crystal_cost=VALUES(crystal_cost), deuterium_cost=VALUES(deuterium_cost), power_draw=VALUES(power_draw), output_bonus=VALUES(output_bonus)');
if($intQ){while($body=$intQ->fetch_assoc()){
  $key=(string)$body['seed_code']; $type=(string)$body['body_type']; $climateProfile=$climateProfiles[rng($seed,$key.'-climate-profile',count($climateProfiles))]; $geology=$geologies[rng($seed,$key.'-geology',count($geologies))]; $ecology=$ecologies[rng($seed,$key.'-ecology',count($ecologies))]; $faction=$factions[rng($seed,$key.'-faction',count($factions))]; $settlement=$settlements[rng($seed,$key.'-settlement',count($settlements))]; $tech=1+rng($seed,$key.'-tech',9); $power=20+rng($seed,$key.'-power',81); $economy=min(100,max(1,(int)$body['richness']+rng($seed,$key.'-economy',31)-15)); $difficulty=min(99,max(5,100-(int)$body['habitability']+rng($seed,$key.'-difficulty',31))); $effect=$effects[rng($seed,$key.'-effect',count($effects))]; $atmosphere='Survey profile: '.$climateProfile; $resource='Richness '.(int)$body['richness'].'% / extractive index '.(int)($economy); $hazard='Risk index '.(int)$difficulty.' / dynamic navigation conditions'; $bodyId=(int)$body['body_id']; $intStmt->bind_param('ssssssssiiiisi',$geology,$ecology,$climateProfile,$atmosphere,$resource,$hazard,$faction,$settlement,$tech,$power,$economy,$difficulty,$effect,$bodyId); $intStmt->execute();
  $designTypes=$type==='anomaly'?['anomaly_lab','research']:($type==='station'?['trade','research','defense','power']:['habitat','mine','research','power','defense','terraforming']);
  foreach($designTypes as $designType){$designName=ucwords(str_replace('_',' ',$designType)).' Blueprint';$level=1+rng($seed,$key.'-'.$designType.'-level',4);$metal=500*$level;$crystal=300*$level;$deut=100*$level;$draw=40*$level;$bonus=4*$level;$unlocked=(int)($type==='station'||$type==='anomaly'||rng($seed,$key.'-'.$designType.'-unlock',100)>55);$designStmt->bind_param('issiiiiii',$bodyId,$designType,$designName,$metal,$crystal,$deut,$draw,$bonus,$unlocked);$designStmt->execute();}
}}
echo "Universe seeded: {$name} / {$seed}\n";
