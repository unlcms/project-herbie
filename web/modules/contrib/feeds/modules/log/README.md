Feeds Log
=========
This module gives you more insight in what happens during an import.

It covers the following information:
- Which source data was used for the import?
- Per imported item, how did the source item look like after parsing?
- Which entities got created, updated or deleted? And which did fail to import?

**Beware! This module logs a lot of data. If you regularly run large imports, a
lot of data can get written to the filesystem and to the database.**


## Files logged on the filesystem
There are two types of files logged on the filesystem:
 * Fetched source data
 * Processed items

### Fetched source data
For each import, fetched data is logged on the filesystem. By default this is
in a folder called `private://feeds/logs/[import_id]/source/`.

### Processed items
Each processed item is logged on the filesystem. By default this is in a folder
called `private://feeds/logs/[import_id]/items/`.


## Configuration
There are three levels of configuration:
 * Global settings at /admin/config/content/feeds_log;
 * Settings per feed type;
 * Settings per feed.

### Global settings
Here you can configure the following:
 * How long logs should be kept;
 * Where on the filesystem to store fetched sources and processed items;
 * Overload protection: if an import ever happens to get in an (infinite) loop,
   Feeds Log stops after a certain amount of iterations with logging as else the
   filesystem could fill up with logged sources or processed items. You can
   configure the maximum allowed amount of logs per feed in a certain
   timeframe.

### Settings per feed type
Logging can be enabled per feed type. It is enabled by default. Additionally,
you can configure what kind of data you are interested in:
 * Logging can be enabled/disabled per operation (created, updated, failed,
   etc.)
 * Per operation you can configure whether or not you are interested in the
   processed item. Processed items can potentially take up a lot of space on the
   filesystem.
 * The fetched source. The fetched source can be big, so just as processed items
   it can take up a lot of space on the filesystem.

### Settings per feed
Logging can be enabled/disabled per feed, but logging on the feed type must be
enabled in order to enable logging for a feed. By default, logging is enabled
for the feed.
Feeds Log can automatically disable logging for a feed if an import gets in an
infinite loop. This is an effect of *overload protection* (see 'Global
settings' above). You then have to manually enable logging again if you still
want things logged for this feed.


## Data model

### Feeds Import Log entity type
On each import an entity of type "feeds_import_log" gets created.
This entity type acts as the container for a series of log entries. On this
entity type are stored:
 * Import ID
 * Feed ID
 * Import start time
 * Import end time
 * The user under which the import is ran (can be different from who triggered
   it)
 * Path to logged source files

### Database table "feeds_import_log_entry"
This table contains the individual log entries. Each entry represents an
operation on a processed item. It tells if a certain entity was created, updated
or cleaned or if an item failed to import. It contains a reference to the Feeds
Import Log entity and a reference to the Feed entity.
