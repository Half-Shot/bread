Testing Procedure
=================

This is the testing procedure that must be followed on each minor build (so for each 0.X0 build). 

Each test **must** be completed or it cannot be branched.

- Read code through, check it makes sense. Remove any useless code.
- Run it locally.
    - Any web server will do, this is just to check no immediate bugs occur.
    - This includes testing each official module.
- Test it with some other users.
    - This can be quite time consuming depending on tester and bread's complexity.
    - This is not a strict requirement *unless* its a LTS build.
- Run in a virtual machine.
     - Use Debian stable and/or Ubuntu LTS. It must be set-up and working **without** any sneaky code changes.
     - Lighttpd, Nginx and Apache should be tested but any one of them for non-LTS will                           do.
- Fix any bugs that might have appeared.
    - Obviously crucial or you will forever have a guilty conscience.
    - This includes high usage of resources too, cause bread likes to maintain < 500 kilobytes for core.
    - If any bugs occur, retest. If the bug is minor you can skip user testing, but virtual machine and local testing MUST be rerun.
    
###What to test?

- The bread release candidate.
- All official modules.    
- EVERY feature these modules and bread supports.
- One theme can be used, but vanilla bread must always remain compliant.
