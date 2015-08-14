Dezi_Client
============

Dezi_Client is a PHP client for the Dezi search platform.

Copyright 2011 by Peter Karman. Release under the MIT license. See LICENSE file.

See http://dezi.org/

## Developers ##

If you are working from a git clone of the dezi-client-php repository, you need to be aware
that the plugin uses git submodules in order to include the Dezi_Client dependencies. After
you have cloned dezi-client-php, you need to:

    % git submodule init
    % git submodule update

Running the test suite requires a local Dezi server running on port 5000 (the default).
You must turn off autocommit for the test suite to work.

In one terminal window:

    % dezi --no-ac

And then in another window:

    % make test


