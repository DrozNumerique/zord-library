<div class="page">
<h2>Principles of digital publishing</h2>
<ul>
	<li>These texts result from exact conversion of the paper version.</li>
	<li>The page numbers of the paper version have been preserved in order
		to allow an identical citation. They appear in the right margin of the
		text in the form {p. aaa}.</li>
	<li>The cutting out of the preliminary pages was reviewed and
		references for the digital version were added.</li>
	<li>References to the original publications are situated between square
		brackets [ ].</li>
</ul>

<h2>User features</h2>
<ul>
	<li>Full text online</li>
	<li>Search (for more details see <a
		href="#searchTips">here</a>)
		: terms, jokers (*, ?), phrases between quotes ("Analogi* fid*"),
		collocations (Olevianus NEAR Ursin*), négation (-)
	</li>
	<li>Basket of bibliographical references</li>
</ul>


<h2>Counter usage reports access :</h2>
    <ul>
		<li>Click on the "Login" button in the menu bar of either portals,
		<li>Enter you login/psw in the appropriate fields,
            <ul>
                <li>Your login name is the contact email you provided to us when you purchased your subscription. We recommend that you keep this email as a login name, in case of a staffing change, or provide us with a generic email, not nominal. This is the address where you will be sent the link allowing you to choose or renew your password;</li>
		        <li>If you have never created a password for this account, or if you have forgotten yours, enter your email address in the "Forgot ?" field and click on "Login". You will then receive a (new) link.</li>
            </ul>
        </li>
        <li>Once the connection is assured in this "Admin" mode, you will see a link labeled "Counter" in the menu bar. At this point, all you have to do is enter the dates that you are interested in and click on the “Report” button. The data will be displayed in the browser and can also be download as an Excel document.</li>
	</ul>


<h2>Technical specifications</h2>

<dl>
	<dt>
		<a href="http://www.tei-c.org/index.xml">XML/TEI</a>
	</dt>
	<dd>
		Texts are encoded in XML according to the <i>Text Encoding Initiative</i>.
	</dd>
	<dt>
		<a href="http://www.projectcounter.org/">COUNTER</a>
	</dt>
	<dd>
		Portal traffic is recorded, according to <a
			href="https://www.projectcounter.org/wp-content/uploads/2016/01/COPR4.pdf">the COUNTER Code of
			Practice for e-Resources: Release 4 (April 2012)</a> :
		<ul>
			<li>Book Report 2 : Number of Successful Section Requests by Month
				and Title</li>
			<li>Book Report 5 : Total Searches by Month and Title</li>
		</ul>
	</dd>
	<dt>Perennial access</dt>
	<dd>This site apply the OpenURL standard which allows subscribers
		(libraries) to always redirect their readers to requested texts, even
		if their web address (URL) has changed. It thus ensures perennial
		access to digital resources.</dd>
	<dt>MARCXML/MODS</dt>
	<dd>
		Instructions for the electronic texts are available in the MARCXML and
		MODS format (one of the Library of Congress standards, <a
			href="http://www.loc.gov/standards/mods/" target="_blank">http://www.loc.gov/standards/mods/</a>),
		notably for the declaration of electronic resources in an integrated
		library system.
	</dd>
	<dt>
		<a href="https://github.com/DrozNumerique/Zord" target="_blank">ZORD</a>
	</dt>
	<dd>
		This portal is run with the free Zord framework, the development of
		which was ordered by Droz and the sources for which are available on
		GitHub: <a href="https://github.com/DrozNumerique/Zord"	target="_blank">https://github.com/DrozNumerique/Zord</a>
	</dd>
</dl>
		<h2 id="queryTips">Search tips</h2>
		<p>The complete book is the documentary unit. Results are given by “chapter” in each book. For this reason, the result in a chapter may not seem to follow the criteria for the search, such as <code>“femme +paradis”</code>, and only offer occurrences of <code>“femme”</code>. The search demand is nevertheless respected, because the book in fact contains elsewhere the term <code>“paradis”</code>, presented in the results for another chapter of the same work.</p>
<ul>
  <li>For simple search, only <b>exact terms</b> will match: <code>parad</code> won't find <del>paradis</del>.</li>
  <li><b>Truncation</b>: <code>*</code> is a substitute for any number of characters. <code>parad*</code> will match <samp>paradis</samp>, <samp>paradoxa</samp>, <samp>paradoxal</samp>, <samp>paradoxalement</samp>, <samp>paradoxorum</samp>, etc.
  <br/>Note: the asterisk (<code>*</code>) must be at the end of the keyword, otherwise its behavior is unpredictable: <code><del>ch*ute</del></code> will match <samp>chant</samp>, <samp>chasse</samp>, etc.</li>
  <li><b>?</b> replaces a character: <code>te?t</code> will produce <code>text</code> as well as <code>test</code>. <code>ro?</code> will produce <code>roy</code>, <code>roi</code> or <code>ros</code>.</li>
    <li>Searching is <b>case-insensitive</b>: <code>paradis</code> and <code>Paradis</code> match the <b>same</b> results.</li>
  <li>Searching is <b>diacritics-sensitive</b>: <code>péché</code> and <code>pêche</code> match <b>different</b> results.</li>
  <li>The default multi-term query operator is <b>OR</b>: <code>paradis terrestre</code>, <code>terrestre paradis</code> match the <b>same</b> results. The search engine will find all instances of each term only in chapters containing those two terms.</li>
  <li>Use <b>OR</b> to search for multiple terms: <code>paradis OR terrestre</code> will find <b>more</b> results than <code>paradis terrestre</code>. The search engine will find all instances of each term in the whole corpus.
  <!-- <br/>Caution: OR can't be used when searching for an exact expression (in "") nor with NEAR. -->
  </li>
  <li><b>NOT (ou -)</b> allows for searching in a document that contains a term and not the other: <code>paradis NOT terrestre</code> or <code>paradis -terrestre</code>.</li>
<li><b>+</b> permet de rechercher les document contenant nécessairement ce terme : <code>paradis +femme</code>.</li>
	<li><b>Grouped searching</b> : (<code>paradis NOT terrestre</code>) AND femme</li>
	<li><b>Vague searching</b>. <code>paradi~2</code> produce all terms close orthographically :<code>parodie</code>, <code>paraît</code>. <code>parati</code>, <code>paradis</code>, <code>hardi</code>, etc.
		<br/><code>paradi~1</code> will produce terms that are orthographically close within one letter.</li>
	<li>Enclose terms in double quotes (<code>""</code>) to find <b>exact expressions</b> : <code>"royaume de dieu"</code> will match <samp>royaume de Dieu</samp>.
  <br/>Note: the search is case-insensitive, but diacritics-sensitive.
  <br/>Note: truncation is allowed in searching for exact expressions: <code>"par* ter*"</code> will find <samp>paradis terrestre</samp> and also <samp>par terre</samp>, <samp>pars tertia</samp>, etc.
  <br/>Note: OR can't be used when searching for an exact expression.
  <br/>Warning: the number of occurrences is the sum of the occurrence of each term in the expression; it is not the count of the occurrences of the expression as a whole.</li>
  <li><b>Proximity (or co-location)</b> : the operator <b>~</b> allows for the searching of terms or expressions separated by a few words : <code>"(paradis terrestre) femme"~2</code> will find <samp>Icy voyez Adam par son peché, Du paradis terrestre dechassé, Sa femme aussi hors de toute liesse.</samp>.
	<br/>The results present one line for each occurrence of the found term in order to preserve the presentation centered around a key word.
	<br/>N.B. The default maximum distance between two terms is ten words (<code>"(paradis terrestre) femme"~10</code>).
	<br/>The number of occurrences corresponds to the sum of all occurrences of all the terms searched.</li>
</ul>
<h3>Search parameters</h3>
<ul>
  <li><b>All texts</b>. The search bar of the Research page finds results in the entire text base.</li>
  <li><b>Filter by collection(s)</b>. Search may be restricted to one or more collections (check the box of the desired collection(s) below the search bar).</li>
  <li><b>Filter by date</b>. Search may be restricted to a time period using the two fields for <i>Year</i>. Chronological search is restricted to the historical texts (modern studies are excluded). Chronological search may be combined with a collection filter.</li>
  <li><b>Search in one book</b>. When browsing a book, the search bar on the left will search in that book only.</li>
</ul>
<h3>Results and navigation</h3>
<ul>
  <li><b>Bibliographic table</b>. The welcome page displays the list of the books as a bibliographic table. To access the full text of a book, click a title.
  <br/>Columns with arrows in the header can be sorted by clicking on them.</li>
  <li><b>Timeline</b>. The results page displays a timeline showing the chronological distribution of the occurrences. You can narrow the time frame of your search by clicking on the bars in the timeline.</li>
  <li><b>Concordance</b>. The default view of a concordance shows the keyword at the center. Hovering over an excerpt displays the rest of the sentence. Click a keyword to go to its location in the text.</li>
  <li><b>Navigating concordance</b>. The book covers shown on the left panel serve as a table of contents for a concordance; to jump to the results in a specific book, click on its cover.</li>
  <li><b>Browsing concordances in the text</b>. In the text, all the occurrences of the keyword are highlighted, and surrounded by brackets. Click <tt>&lt;</tt> to go to the previous occurrence; click <tt>&gt;</tt> to go to the next one.</li>
</ul>
</div>
