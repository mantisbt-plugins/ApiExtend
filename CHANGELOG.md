# APIEXTEND CHANGE LOG

## Version 1.2.1 (August 25th, 2019)

### Documentation

- **readme:** add initial info on /issues endpoint

### Refactoring

- the /issues endpoint should be authenticated

## Version 1.2.0 (August 25th, 2019)

### Documentation

- **readme:** udpate issues submit section

### Features

- add support for retrieving tickets using a set of specified filter property-value pairs, as opposed to the default mantis api which requires a saved filter and a filter id to be passed.  See the readme file for details.

## Version 1.1.5 (August 8th, 2019)

### Bug Fixes

- php path is being populated with multiple entries of core_path with some of spm plugins
- tgz release package does not contain the plugin directory as the top level

## Version 1.1.4 (August 3rd, 2019)

### Build System

- **ap:** add gzip tarball to mantisbt and github release assets

## Version 1.1.3 (August 3rd, 2019)

### Features

- show the success redirect when saving config settings

### Bug Fixes

- set cache-control-max-age on issues count badges

## Version 1.1.2 (August 2nd, 2019)

### Build System

- **app-publisher:** correct github user in publishrc

### Documentation

- **README:** clean up badge links, add section on submitting issues [closes #1]

### Bug Fixes

- GitHub camo image caching is breaking badge updates.  Add cacheSeconds parameter to query string in requests to shields.io for attempt 1.

## Version 1.1.1 (July 29th, 2019)

### Bug Fixes

- issues count badge counts show blank instead of '0' when there are no issues

## Version 1.1.0 (July 29th, 2019)

### Build System

- **app-publisher:** set interactive flag to N for non-interactive setting of new version during publish run (compliments of ap v1.10.4 update)
- add config to publishrc for first mantisbt release

### Documentation

- **readme:** add screenshot of version badges
- **readme:** update version badges
- **readme:** update version endpoint section info

### Features

- add support for current and next version api endpoints

## Version 1.0.1 (July 27th, 2019)

### Build System

- **ap:** fix build in case where release zip already exists in build direcctory

### Documentation

- **readme:** update api info
- **readme:** update installation section and issues badge links

### Miscellaneous

- Update license to GPLv3

## Version 1.0.0

- Initial Release

