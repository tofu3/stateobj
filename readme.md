State Objects
=====

## "Introduction" or "Why?" ##
The idea is that you shouldn't have to write all the database back-end code BEFORE you make
your first working prototype of the program you just had an awesome dream about.  
I remember being a kid and thinking that programming was a piece of cake, that is, until I realized
that things didn't remember the state they were in last time I ran my code.  
There was this whole new world of exciting information storage alternatives, and it just felt a bit overwhelming.

*Should I use signed integers or will unsigned do?*  
*Will this field ever be used to store more than ten characters?*

Who cares? Use __State Objects__!

## Examples
      require('inc.stateobj.php');
      $so = new SimpleStateObj('foo');
      $so->counter += 1;
      print 'This script has been called '.$so->counter.' times!';