# R3BL CPT UI

###### Version 1.0.0

*Enables a quick and easy UI that creates custom post types.*

**License: GPL2**

Copyright 2018  James Cook  (email : jcook@r3blcreative.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

## TODO
- Re-work icon column in DB to take a JSON object and store icon metadata:
- - id
- - label
- - styles
- - unicode
- Re-work Admin Add page icon inputs to capture all of the above icon metadata
- Re-work js ajax code to pass icon input metadata for php ajax validation
- Re-work php ajax validation code to format input metadata for DB
- Re-work Admin Edit page icon inputs to recieve all of the above icon metadata
- Enable FA searching functionality on icon picker
- Figure out how to combine add and edit templates into one template
- Need to be able to validate unique tax slug