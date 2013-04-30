Verband
=======

Verband is Dutch for "Context". This framework allows for the dynamic assembly of its process flow via application-defined contexts.

Server Installation (Ubuntu)
----------------------------

```
sudo apt-get update
sudo tasksel install lamp-server
sudo a2enmod php5
sudo a2enmod rewrite
sudo vim /etc/apache2/sites-available/default
```

Add the following:

```
<Directory /var/www>
  AllowOverride All
</Directory>
```

Site Setup (Apache2)
--------------------

```
sudo touch /etc/apache2/sites-available/<yourSite>.com
echo "<VirtualHost *:80>" | sudo tee -a /etc/apache2/sites-available/<yourSite>.com
echo "  ServerAdmin <yourEmail>@<yourSite>.com" | sudo tee -a /etc/apache2/sites-available/<yourSite>.com
echo "  ServerName <yourSite>.com" | sudo tee -a /etc/apache2/sites-available/<yourSite>.com
echo "  ServerAlias www.<yourSite>.com" | sudo tee -a /etc/apache2/sites-available/<yourSite>.com
echo "  DocumentRoot /var/www/<yourSite>.com" | sudo tee -a /etc/apache2/sites-available/<yourSite>.com
echo "</VirtualHost>" | sudo tee -a /etc/apache2/sites-available/<yourSite>.com
sudo a2ensite <yourSite>.com
sudo service apache2 restart
echo "127.0.0.1     <yourSite>.com" | sudo tee -a /etc/hosts
```

Project Installation
--------------------

```
cd /var/www
curl -s http://getcomposer.org/installer | php
php composer.phar create-project --stability=dev verband/application <yourSite>.com
rm -fr .git
git init
git add .
git commit -m "first commit"
git remote add origin <Your Git repository>
git push -u origin master
```

Quick and Dirty
---------------

```xml
<?xml version="1.0"?>
<application>
  <package name="Verband\Framework">
		<remove process="Verband\Framework\Process\ResourceRouter" />
	</package>
	<package name="Verband\Doctrine" />
	<package name="CodeOtter\Memcached" />
	<package name="CodeOtter\Session" />
	<process name="Verband\Framework\Process\ResourceRouter" />
	<package name="Verband\Content" />
	<package name="CodeOtter\Rest">
		<after process="ControllerExecuter" inject="Verband\Doctrine\Process\Persist" />
	</package>
</application>
```

There.  I have represented the implied ontology of all PHP frameworks in fourteen lines of markup.

What the hell just happened?
----------------------------

Once upon a time, there was a framework for PHP.  And it was good.

Web developers eschewed all other programming patterns and embraced the ORM/MVC/RPC paradigm.  And it was good.

From this, they created thirty more frameworks, each one a slight variation of a previous ontology.  The goodness grew cold.

Web developers pushed this paradigm to the extreme and began doing straight-up code lifts from Ruby and Java.  Then the goodness stopped.

Today, the Framework of Babel reigns supreme in PHP Land.  Each enclave claims their ontology is the best.  This argument is pointless because it is subjective.

Verband doesn't care about your ontology's prowess.  Verband lets you assemble ontology dynamically at run-time.

You're a chatty bitch.  The hell is an ontology?
------------------------------------------------

_"In theory, an ontology is a "formal, explicit specification of a shared conceptualization". An ontology renders shared vocabulary and taxonomy which models a domain with the definition of objects and/or concepts and their properties and relations" ~[The Wiks](http://en.wikipedia.org/wiki/Ontology_(information_science))_

Every single framework in existence implies their ontology, not from the code base or the patterns they choose, but by assuming a context where it is the center of the universe.  Even the most religious adherence to functional programming cannot ensure an absolute separation of concern between all components of a framework.  As a result, trying to tinker with a framework's process flow to be more reflective of project requirements becomes very problematic.

The ontology of all frameworks assumes a static process flow. (input -> processing -> output)  To get into the space in between processes (where project requirements constantly find themselves wanting to be), things like event listeners and hooks are utilized, but even these are bound to an unresponsive ontology.  At best, they simulate dynamic ontology in a fixed manner.

Verband allows a developer to define application-specific ontologies by chaining together custom contexts.  This liberates the framework from having a fixed ontology, allowing the context of a project to be represented in a functional, reusable, and dynamic way.

Okay, Mr. Philosopy.  Show me the good stuff.
---------------------------------------------

Let's assume the ontology of our framework defines the process flow to be the following:

```
Framework Initialize->
 Request Initialize->
  Database Initialize->
   Memcached Initialize->
    Session Initialize->
     Resource Detection->
      Content Initialize->
       REST Execution->
        Database Persistance
```

**Q: How do you traditionally represent this in a framework?**

**A: Spaghetti style.**

The short answer is you can't.  You can only imply the process flow at a conceptual level while enforcing it via scattered invocations of methods and classes throughout your project.

But with Verband, you define the process flow upfront.  Let me translate the previously mentioned markup into English:

<?xml version="1.0"?>
<application>
  <package name="Verband\Framework">
  	<remove process="Verband\Framework\Process\ResourceRouter" />
	</package>
	<package name="Verband\Doctrine" />
	<package name="CodeOtter\Memcached" />
	<package name="CodeOtter\Session" />
	<process name="Verband\Framework\Process\ResourceRouter" />
	<package name="Verband\Content" />
	<package name="CodeOtter\Rest">
		<after process="ControllerExecuter" inject="Verband\Doctrine\Process\Persist" />
	</package>
</application>

```
Let us define the application
The application uses the Framework package
In the Framework package, remove the ResourceRouter process.
The application then uses the Doctrine package.
The application then uses the Memcached package.
The application then uses the Session package.
Add the ResourceRouter to the application.
The application then uses the Content package.
The application then uses the Rest package.
After the REST package's ControllerExecuter process, add the Doctrine Persist process.
```

The Framework is now aware of its work flow, where each Context is defined.  It even knows what context an Exception is thrown from.


General rules about contexts
----------------------------

* Each Context Process is passed the current context of the workflow.
* Aditionally, each Context Process is given the output of the previous Context Process as its input. (Think monads)
* A Context can register the state of a value to itself with ```setState($key, $value)```, which can be accessed by its children via ```getState($key)```.  For example, to access the Framework singleton from any context, simply call ```$context->getState('framework')```.  (think lexical scoping)
* An Applications can have many Packages, and each Package defines its own Processes. 
