email address: rdmpage@gmail.com

Title  DNA Barcode Browser

Abstract and rationale (max. 1000 words): The abstract should describe your submission, why it matters to GBIF communities, and how it intends to serve their needs. *

Genomic data such as DNA barcoding and metagenomics is becoming an increasingly important contribution to GBIF. Classical occurrence data (a taxonomic name, a locality, a date) can be readily displayed as lists of occurrences, or as dots on a map. However, these visualisations ignore the very thing that makes sequence data unique, namely the sequence. Given a DNA sequences we should be able to query GBIF and find out where similar sequences occur. Given a geographic area, we should be able to find sequences known from samples in the area. Furthermore, we should be able to explore sequences using the appropriate tools, such as phylogenetic trees. Not only are trees a useful way summarise sequence data, they also enable us to go beyond counting species and to measure phylogenetic diversity.

My entry to the 2020 challenge is a proof of concept for incorporating DNA sequence data into standard interfaces for exploring biodiversity data. It does this by using "alignment free" methods for making trees in a web browser, and a method for searching DNA sequences that uses the same search engine (Elasticsearch) that underpins GBIF (for more details see the source code repository). This enables sequence searching and phylogeny construction to be interactive, so the user can explore data in real time. 

The sequence data comes directly from BOLD, but is first converted into Darwin Core Archive (DwCA) format before being indexed. GBIF itself could not be used as a source of these data (even though it has DNA barcode data complete with sequences) because GBIF downloads do not include the sequences in the DwCA.

The primary goal of this tool is one of data exploration, so that the user gets a sense of what barcode data is available, and the insights we can get from phylogenetic trees of that data. For example, a cursory glance at a tree can tell us whether we have densely sampled closely related lineages (e.g., lots of tight clusters or “BINs”), or sparsely sampled divergent linages. 

For this to be the basis of more analytical approaches, we would need to introduce measures of phylogenetic diversity and sample geographically comparable areas. For example, we could divide the globe into comparable areas using a discrete grid and use the alignment-free approach to build phylogenies for samples of sequences taken from each cell in that grid. This would enable us to construct a global map of phylogenetic diversity.

Operating instructions: how does your submission work? Provide simple, clear step-by-step instructions here (detailed technical aspects should be documented in your submission's website or repository). *

The entry is available at https://dna-barcode-browser.herokuapp.com

There are two ways to use the app. The first is modelled on BLAST. In the “DNA search” box you paste in a DNA sequence and press “Search” (if you don’t have a sequence then click on “Example”. The app searches the Elasticsearch database of DNA barcodes for similar sequences, if it finds any it returns them and then computes a phylogenetic tree for those sequences. The location of the sequences is also plotted on a map, and displayed as a list.

The second method is to search spatially. Using the “Geographic Search” map you can draw a polygon on the map that corresponds to the area within which you want to search. The database retrieves sequences in that area, and as before, computes a tree, shows the sequences on a map, and displays a list.

The tree and map are interactive, you can pan and zoom the tree and the map using your mouse. The total branch length of the phylogeny (in units of pairwise *k*-mer distance) is displayed as a crude measure of phylodiversity (Faith, 1992) to help give a sense of how much genetic diversity the tree represents. You can also download the (unaligned) DNA barcodes in [FASTA](https://en.wikipedia.org/wiki/FASTA_format) format, get the pairwise distances in [NEXUS format](https://en.wikipedia.org/wiki/Nexus_file) suitable for input into programs such as [PAUP*](https://paup.phylosolutions.com) and [Splitstree](http://www.splitstree.org), and download the tree in [Newick format](https://en.wikipedia.org/wiki/Newick_format).

Details on the methods used to search for sequences and create the tree are given in the source code repository https://github.com/rdmpage/dna-barcode-browser

Link to visuals: include introductory video or screencast (max 5 min.), screenshots, presentation, poster, infographics etc. Good visuals demonstrate and describe the submission, its features and its benefits. *

There is a screencast at https://youtu.be/jjXuWfofMPg

Link(s) to source location: direct the jury to your online submission's website or repository. *

https://github.com/rdmpage/dna-barcode-browser



