<div class="page">
<h2>Principes d&rsquo;&eacute;dition num&eacute;rique</h2>
<ul>
	<li>Nouveautés comme textes du fonds sont numérisés &agrave;
		l&rsquo;identique, de leur &eacute;dition papier. Ils peuvent donc
		être cités exactement de la même façon, à la note et à la page près.</li>
	<li>Les num&eacute;ros de page de l&rsquo;&eacute;dition papier ont
		d&eacute;lib&eacute;r&eacute;ment &eacute;t&eacute; conserv&eacute;s à
		cette fin. Ils apparaissent en marge droite du texte sous la forme {p.
		aaa},</li>
	<li>Les notes et appels de notes portent les m&ecirc;mes chiffres que
		l&#39;&eacute;dition papier,</li>
	<li>Les r&eacute;f&eacute;rences aux &eacute;ditions t&eacute;moin se
		trouvent entre crochets [ ] en marge de gauche.</li>
</ul>
<h2>Fonctionnalit&eacute;s utilisateur</h2>
<ul>
	<li>Texte int&eacute;gral en ligne,</li>
	<li>Affichage/obfuscation des r&eacute;f&eacute;rences (N&deg; de
		ligne, de vers, r&eacute;f&eacute;rence aux t&eacute;moins, n&deg; de
		page de l&#39;&eacute;dition papier...).</li>
	<li>Recherche&nbsp; (voir le détail <a
		href="#searchTips">ici</a>) : termes, jokers (*, ?), expressions
		entre guillements (&quot;Analogi* fid*&quot;), co-locations ("(paradis
		terrestre) femme"~2), n&eacute;gation (-)...
	</li>
	<li>Panier de r&eacute;f&eacute;rences bibliographiques et citations (à
		l'identique du papier et dans différents styles académiques),</li>
	<li>Compatibilité complète avec le logiciel de références
		bibliographiques libre Zotero,</li>
</ul>

<h2>Procédure d'accès aux rapports Counter :</h2>
    <ul>
		<li>Cliquer sur le bouton « Connexion » dans la barre de menu du portail,
		<li>Entrer vos login/psw dans les champs indiqués,
            <ul>
                <li>Votre login est le email de contact que vous nous avez donné lors de l'achat de votre abonnement. Nous vous recommandons de conserver cet email en guise de login y compris dans le cas d'un changement de personnel, ou de nous indiquer un email générique, non nominatif. C'est à cette adresse que vous sera adressé le lien vous permettant de choisir ou renouveler votre mot de passe ;</li>
		        <li>Si vous n'avez jamais généré de mot de passe, ou si vous l'avez oublié, rentrez votre email dans le champ « Oublié ? » et cliquer sur « Connexion ». Vous recevrez alors un (nouveau) lien.).</li>
            </ul>
        </li>
        <li>Une fois la connexion assurée dans ce mode « Admin » vous verrez apparaître dans la barre de menu le lien « Compteur ». Vous n'avez alors qu'à indiquer les dates pour l'intervalle qui vous intéresse et à cliquer sur le bouton « Rapport ». Les données s'affichent dans le navigateur mais vous pouvez également les télécharger au format Excel.</li>
	</ul>


<h2>Sp&eacute;cifications techniques</h2>
<dl>
	<dt>
		<a href="http://www.tei-c.org/index.xml">XML/TEI</a>
	</dt>
	<dd>
		Le texte des livres est structur&eacute; selon le sch&eacute;ma XML de
		la <i>Text Encoding Initiative</i> (standard acad&eacute;mique
		international).
	</dd>
	<dt>
		<a href="http://www.projectcounter.org/">COUNTER</a>
	</dt>
	<dd>
		Des rapports de fr&eacute;quentation du portail seront
		g&eacute;n&eacute;r&eacute;s selon la norme <a
			href="https://www.projectcounter.org/wp-content/uploads/2016/01/COPR4.pdf" target="_blank">The
			COUNTER Code of Practice for e-Resources: Release 4 (April 2012)</a>&nbsp;:
		<ul>
			<li>&ldquo;Book Report 2&rdquo; : nombre de sections par livres et
				par mois,</li>
			<li>&ldquo;Book Report 5&rdquo; : recherches et sessions par titre et
				par mois.</li>
		</ul>
	</dd>

	<dt>Accès pérenne</dt>
	<dd>Les portails intégrent la norme OpenURL qui permet aux abonnés
		(bibliothèques) de toujours rediriger leurs lecteurs vers les textes
		demandés, quand bien même leur adresse web (URL) aurait changé. Cela
		assure ainsi un accès pérenne aux ressources numériques.</dd>
	<dt>MARCXML/MODS</dt>
	<dd>Les notices des textes &eacute;lectroniques sont disponibles au
		format MARCXML et MODS (l'un de trandards de la Librairy of Congress,
		http://www.loc.gov/standards/mods/), notamment pour d&eacute;clarer
		les ressources &eacute;lectroniques dans un SIGB (outil de catalogage
		en biblioth&egrave;que).</dd>
	<dt>
		<a href="https://github.com/DrozNumerique/Zord" target="_blank">ZORD</a>
	</dt>
	<dd>
		Ce portail est propuls&eacute; par le logiciel libre Zord (Licence
		LGPL), dont le d&eacute;veloppement a &eacute;t&eacute;
		commandit&eacute; par les &eacute;ditions Droz et dont les sources
		sont disponibles sur GitHub :&nbsp;<a href="https://github.com/DrozNumerique/Zord/" target="_blank">https://github.com/DrozNumerique/Zord</a>
	</dd>
</dl>
		<h2 id="queryTips">Conseils pour la recherche</h2>
		<p>L'unité documentaire est le livre complet. Les résultats sont donnés par "chapitre" dans chaque livre. Pour cette raison le résultat dans un chapitre peut ne pas sembler suivre les exigences de la recherche, telle que <code>femme +paradis</code>, et ne présenter que des occurrences de <code>femme</code>. L'exigence de recherche est pourtant respectée car le livre contient bien par ailleur le terme imposé <code>paradis</code>, exposé dans les résultats pour un autre chapitre du même ouvrage.</p>
<ul>
	<li>La recherche simple se fait sur les <b>termes exacts</b> : <code>parad</code> ne trouvera pas <del>paradis</del>.</li>
	<li><b>Troncature</b>: <code>*</code> remplace n’importe quel caractère en fin de mot ; <code>parad*</code> trouvera <samp>paradis</samp>, <samp>paradoxa</samp>, <samp>paradoxal</samp>, <samp>paradoxalement</samp>, <samp>paradoxorum</samp>, etc.
		<br/>N. B. Le symbole de la troncature (<code>*</code>) doit être à la fin du terme, sinon le résultat de la recherche est imprévisible : <code><del>ch*ute</del></code> trouvera <samp>chant</samp>, <samp>chasse</samp>, etc.</li>
	<li><b>?</b> remplace un caractère : <code>te?t</code> renverra <code>text</code> aussi bien que <code>test</code>. <code>ro?</code> renverra <code>roy</code>, <code>roi</code> ou <code>ros</code>.</li>
	<li>La recherche est <b>insensible à la casse</b> : <tt>paradis</tt> et <tt>Paradis</tt> renvoient les <b>mêmes</b> résultats.</li>
	<li>La recherche est <b>sensible aux accents</b> : <code>péché</code> et <code>pêche</code> renvoient des résultats <b>différents</b>.</li>
	<li><code>paradis terrestre</code> ou <code>terrestre paradis</code> renvoient les <b>mêmes</b> résultats, toutes les occurrences de chaque terme dans les chapitres contenant les deux termes.</li>
	<li><b>OR</b> permet de rechercher plusieurs termes. C'est l'opérateur par défaut. Ainsi <code>paradis OR terrestre</code> renvoie autant de résultats que <code>paradis terrestre</code>. Le moteur de recherche renvoie toutes les occurrences de chaque terme dans le corpus entier.</li>
	<li><b>NOT (ou -)</b> permet de rechercher les document contenant un terme a l'exclusion de l'autre : <code>paradis NOT terrestre</code> ou <code>paradis -terrestre</code>.</li>
	<li><b>+</b> permet de rechercher les document contenant nécessairement ce terme : <code>paradis +femme</code>.</li>
	<li><b>Recherche groupée</b> : (<code>paradis NOT terrestre</code>) AND femme</li>
	<li><b>Recherche floue</b>. <code>paradi~2</code> renverra tous les termes orthographiquement proches : <code>parodie</code>, <code>paraît</code>. <code>parati</code>, <code>paradis</code>, <code>hardi</code>, etc.
		<br/><code>paradi~1</code> renverra tous les termes orthographiquement proches à 1 lettre près.
	</li>

	<li>Les guillemets (<code>""</code>) permettent de rechercher des <b>expressions exactes</b> : <code>"royaume de dieu"</code> trouvera les occurrences de l’expression <samp>royaume de Dieu</samp>.
	<br/>N. B. La recherche reste insensible à la casse et sensible aux accents.
	<br/>N. B. Le caractère de troncature est autorisé dans la recherche d’expressions exactes : <code>"par* ter*"</code> trouvera <samp>paradis terrestre</samp>, mais aussi <samp>par terre</samp>, <samp>pars tertia</samp>, etc.
	<br/>N. B. L’opérateur OR n’est pas disponible dans la recherche d’expressions exactes.
	<br/>Attention. Le nombre d’occurrences correspond à la somme de toutes les occurrences de tous les termes de l’expression, autrement dit, pour une expression de 2 termes apparaissant 2 fois, le compteur indiquera 4.</li>
	<li><b>Proximité (ou co-location)</b> : L’opérateur <b>~</b> permet de trouver des termes ou des expressions séparés par quelques mots : <code>"(paradis terrestre) femme"~2</code> trouvera <samp>Icy voyez Adam par son peché, Du paradis terrestre dechassé, Sa femme aussi hors de toute liesse.</samp>.
	<br/>N. B. Les résultats présentent une ligne pour chaque occurrence d’un terme trouvé, afin de conserver la présentation centrée autour d’un mot pivot.
		<br/>N.B. Par défaut, la distance maximale entre deux termes est de 10 mots (<code>"(paradis terrestre) femme"~10</code>).
	<br/>Attention. Le nombre d’occurrences correspond à la somme de toutes les occurrences de tous les termes cherchés.
	</li>
</ul>
<h3>Paramètres de recherche</h3>
<ul>
	<li><b>Chercher dans tous les livres</b>. La barre de recherche de la <a href="<?php echo $baseURL; ?>/page/search">page Recherche</a> lance la recherche sur l’ensemble des livres.</li>
	<li><b>Filtrer par sous-collection(s)</b>. La recherche peut être limitée à une ou plusieurs sous-collections (cocher les cases correspondantes, sous la barre de recherche dans le menu "Filtres").</li>
	<li><b>Filtrer par date</b>. La recherche peut être limitée à une période grâce aux deux champs <i>Date de la source</i>. De fait cette La recherche chronologique porte sur les seuls textes historiques (les études académiques en sont exclues) et peut être couplée avec les filtres de sous-collection.</li>
	<li><b>Exclure/inclure les index, glossaires et bibliographies</b>. Par défaut les index, glossaires et bibliographies sont exclus du champ de la recherche. Pour les y inclure, cochez la case correspondante dans le menu "Filtres".</li>
	<li><b>Chercher dans un livre</b>. Lorsque vous consultez un livre, la première icône dans le menu d'outils à droite lance la recherche dans ce seul livre.</li>
</ul>
<h3>Résultats et navigation</h3>
<ul>
	<li><b>Table bibliographique</b>. La page d’accueil présente la liste des livres dans une table bibliographique. Pour consulter un livre, cliquez sur son titre.
	<br/>Cliquez sur l’en-tête des colonnes où apparaissent des flèches pour trier les livres en fonction du champ correspondant.</li>
	<li><b>Frise chronologique</b>. Pour une recherche, la frise chronologique indique la distribution des occurrences par périodes pour les éditions de sources.
	<br/>Cliquez sur les barres de la frise pour affiner la recherche chronologique.</li>
	<li><b>Concordance</b>. Les concordances sont alignées autour du terme pivot. La phrase contexte apparaît au survol. Cliquez sur le mot clé pour aller à son emplacement dans le texte.</li>
	<li><b>Retour au texte</b>. Lors du retour au texte après une recherche, toutes les occurrences du mot clé sont mises en valeur et entourées de chevrons si d'autre occurrences ont été trouvées dans le même chapitre. Cliquez sur <tt>&lt;</tt> pour aller à l’occurrence précédente ; cliquez sur <tt>&gt;</tt> pour aller à la suivante.</li>
</ul>
</div>
