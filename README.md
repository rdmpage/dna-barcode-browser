# DNA barcode browser

Interactive web app to explore DNA barcode data sourced from DwCA files. 

## Background

The goal of this app is to suggest a possible interface for exploring DNA barcode data in GBIF. As this data becomes more common it raises the issue of how best to display and explore it. Classical occurrence data (a taxonomic name, a locality, a date) can be readily displayed as lists of occurrences, or as dots on a map). Barcode data comes with a DNA sequence that is potentially rich in information that is not made use of in lists or maps. Phylogenetic trees are an obvious visualisation, but computing these can be computationally demanding. For example, given a set of DNA sequences typically we would contract a multiple sequence alignment, then compute a tree using a sophisticated statistical model of sequence evolution. Searching for similar sequences is also computationally challenging. The default approach is to use existing software such as BLAST.

This project attempts to tackle these problems using Elasticsearch, a technology already used in GBIF. By treating DNA sequences as text strings and using Elasticsearch’s built in ngram tokeniser, we can find similar sequences using a full text search. N-grams are substrings of characters, where n is the number of characters in the substring. In the context of biological sequences they are also known as “k-mers”, where k is the length of a subsequence. There are techniques for computing pairwise distances between DNA sequences based on k-mers, which means we can avoid the costly sequence alignment step. Hence, give a set of sequences, either found by searching for similar sequences, or based on geographic search, we can quickly compute a phylogenetic tree.

### Using the app

There are two ways to use the app. The first is modelled on BLAST. In the “DNA search” box you paste in a DNA sequence and press “Search”. The app searches the database for similar sequences, if it finds any it returns them and then computes a phylogenetic tree for those sequences. The sequences are also plotted on a map, and displayed as a list.

The second method is to search spatially. Using the “Geographic Search” map you can draw a polygon on the map that corresponds to the area within which you want to search. The database retrieves sequences in that area, and as before, compute a tree, shows them on a map, and displays a list.

The tree and map are interactive, you can pan and zoom the tree and the map. 

### Limitations and future directions

The interface could be refined and the displays of tree, map, and list linked together using “brushing” (that is, selecting a node in a tree could also highlight the corresponding location in the map and in the list).

The primary goal of this tool is one of data exploration, so that the user gets a sense of what barcode data is available, and the insights we can get from phylogenetic trees of that data. For example, a cursory glance at a tree can tell us whether we have densely sampled closely related lineages, or sparsely sampled divergent linages. 

For this to be the basis of more analytical approaches, we would need to introduce measures of phylogenetic diversity and sample geographically comparable areas. 


### Under the hood

Data in the form of Darwin Core Archives (DwCA) is converted into JSON documents for uploading to Elasticsearch. DwCA could come from GBIF downloads, but for this demo the data comes from BOLD. I have written scripts to retrieve data from BOLD and convert to DwCA format. 

The sequences are indexed as n-grams, which means we can search for matching sequences by doing a full text search. In this way, Elasticsearch mimics a BLAST search. 

To visualise the sequences we compute a phylogenetic tree. This needs to be done “on the fly” so speed is vital. Pairwise distances between the sequences are computed using k-mers, these distances are then used compute a neighbour-joining tree.


## Pipeline for uploading data

### BOLD to DWCA

Given a BOLD identifier (or other query) use ```bold2dwca.php``` to fetch data and convert to a DWCA file. We do this so that we can provide a way to upload BOLD to GBIF, as well as mimic process whereby someone downloads DWCA with barcodes from GBIF.

### DWCA to SQL index

DWCA splits the data into several different files based on data type (e.g., occurrences, sequences, images), but we want to assemble those into a single JSON document for indexing in Elastic. We could do this by creating a document for each occurrence and then updating that document with sequence and image information, but that means we can’t make use of Elastic’s bulk upload feature. Hence, we use ```dwca_to_sql.php``` to store the data (one row per data type).

```
CREATE TABLE `dwca` (
  `id` varchar(32) NOT NULL DEFAULT '',
  `type` varchar(32) NOT NULL,
  `data` text,
  PRIMARY KEY (`id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

We then create a view that lists the unique identifiers:

```
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ids`
AS SELECT
   distinct `dwca`.`id` AS `id`
FROM `dwca`;
```

### Upload to Elastic

To upload the data we take the list of unique identifiers, assemble them corresponding data into a single JSON document, then send that to Elastic. These operations are done by ```to_elastic.php```.


### Elastic indices

We need indices to match sequences, geography, and taxonomic classification.

Need 
```
{
  "index.mapping.total_fields.limit": 10000
}
```
To avoid 
```
{"error":{"root_cause":[{"type":"illegal_argument_exception","reason":"Limit of total fields [1000] in index [dna] has been exceeded"}],"type":"illegal_argument_exception","reason":"Limit of total fields [1000] in index [dna] has been exceeded"},"status":400}
```




## Acknowledgements


