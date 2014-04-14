# General testing guidelines

## The short summary

This list summarizes what will be described in the next few chapters:

- You really should write tests for your code
- Think about your what you want to test and how you can assert the behaviour correctly.
- Isolate your tests and start without any assumptions about your test environment (like a specific user existing). The
  only excuse here is to assume a correct login if you need a database for testing (but not the tables/content of the database!)
- Don't just test correct behaviour - test border cases and invalid input or assumptions to make sure the application handles
  cases that might not occur in a normal test environment
- Use description strings in your assertions, this makes it easy to detect what's going wrong when they fail and it's easier
  to follow what *exactly* you are asserting
- Test methods should be one scenario and the method name should describe this scenario.
  *testLogin* is bad for example (do you test if the login fails? Or if it is correct?
  *testLoginWithCorrectCredentials*, *testLoginWithWrongPassword* is a far better name here
- Your assertions should reflect one test scenario, i.e. don't write one test method that tests if something works **and**
  if it correctly detects errors after it works. Write one test to determine the behaviour with correct input and one that
  tests the behaviour with invalid input.
- Mock external components and inject them into the class you want to test. If your testsubject is not able to use mocked
  dependencies, it's often a design flaw and should be considered as a bug (and be fixed)


## What should be tested

### Writing meaningful tests

Writing tests doesn't ensure that your code is free of errors, but it can test if, in a specific scenario, the behaviour of
your code is as expected. This means that you have to think about your test scenario before writing a test. This involves
three steps:

- Determine what you want to test: Which errors can occur, what is the normal input and what are the border cases.
- Define a test scenario: What datasets make sense for the test?
- How do I write my assertions so they are really meaningful about the correctness of the testcase

Especially the border cases are important, often they lay inside of try/catch blocks or error detection routines. When
looking at your code coverages, these blocks should be covered.

### Example

Let's say you have the following function (the examples in this section are to be considered as php-like pseudocode) :

    function isValidName($name)
    {
        if (hasCorrectFormat($name)) {
            return false;
        }

        if (nameExistsInDatabase($name)) {
            return false;
        }

        return true;
    }


#### Determine what to test:

At first glance there can be 3 scenarios:

1. The username is unique and valid
2. The username has the wrong format
3. The username has the correct format, but exists in the database

But - what happens if the database is down? Should an exception be thrown or should it return false? This case has to be added
to your tests, so you have (at least!) the following scenarios:

1. The username is unique and valid
2. The username has the wrong format
3. The username has the correct format, but exists in the database
4. The username has the correct format, but access to the database fails.


#### Determine meaningful testscenarios

When it comes to creating your testscenario, we start with the easiest one: Test the wrongly formatted username. This
should be pretty straightforward, as we never reach the code where we need the database:

    function testWrongFormat()
    {
        assertFalse(isValidName("$$% 1_', "Assert a name with special characters to be considered an invalid name");
    }

The first and third scenario are more sophisticated (like always, when a database comes into the game). There are two ways
to test this:
- Either you create an empty table on each test, fill them with the users and run the test
- or you Mock the database call with a class that behaves like querying the users and returns true or false in each case

You **shouldn't** create a static database for all tests and assume this one to be existing - these are decoupled from the
actual test and soon get outdated or difficult to reflect all scenarios. Also it's not considered good practice to create
a precondition your tests have to rely on. In this case the second approach makes sense, as the mock class should be rather simple:


    function testCorrectUserName()
    {
        // set no users in the database mock
        $db = new UserDatabaseMock(array());
        setupUserDatabaseMock($db);

        assertTrue(isValidName("hans"), "Assert a correctly formatted and unique name to be considered valid");
    }

    function testNonUniqueUserName()
    {
        // set no users in the database mock
        $db = new UserDatabaseMock(array("pete"));
        setupUserDatabaseMock($db);

        assertFalse(isValidName("pete"), "Assert a correctly formatted, but existing name to be considered invalid");
    }

The exception can be tested by providing invalid db credentials when using a real databse or by extending the mock (which
we will do here):

    function testDatabaseError()
    {
        // set no users in the database mock
        $db = new UserDatabaseMock(array());
        $db->setThrowsException(true);
        setupUserDatabaseMock($db);

        assertFalse(isValidName("hans"), "Assert a correct, unique user to be considered invalid when the database doesn't work");
    }

This, of course, depends on how you want the behaviour to be when the db is down: Do you want to log the error and proceed
as usual or do you want the error to bubble up via an exception.

#### Writing sensible assertions

It's crucial to write sensible assertions in your test-classes: You can write a perfect test that covers the right scenario,
but don't catch errors because you aren't asking the correct questions.

-   Write assertions that cover your scenario - if you test a correct behaviour don't test what happens if something goes wrong
    (this is a seperate scenario)
-   While you should try to write redundant assertions it's better to assert more than to have a missing assertion
-   A failed assertion means that the implementation is incorrect, other assertions are to be avoided (like testing if a
    precondition applies)
-   When testing one function, you have to be naive and assume that everything else is bug free, testing whether other parts
    worked correctly before testing should be made in a different test.

## How to test

Unit tests should only test an isolated, atomic of your code - in theory just one single function - and shouldn't
need much dependency handling. An example for a unittest would be to test the following (hypothetical) class method:

    class UserManager
    {
        /**
        *   returns true when a user with this name exists and the
        *   password for this user is correct
        **/
        public function isCorrectPassword($name, $password)
        {
            // You needn't to know the implementation.
        }
    }

### The wrong way

A unit test for this user could, but should not look like this (we'll explain why):

    use Icinga/MyLibrary/UserManager

    class UserManagerTest extends \PHPUnit_Framework_TestCase
    {
        /**
        *   Test whether an user is correctly recognized by the UserManager
        *
        **/
        public function testUserManager()
        {
            // Connect to the test database that contains jdoe and jsmith
            $mgrConfg = new \Zend_Config(array(
                "backend" => "db",
                "user"    => "dbuser.."
                "pass"    =>
                // all the other db credentials
            ));

            $mgr = new UserManager($mgrConfig);

            $this->assertTrue($mgr->isCorrectPassword("jdoe", "validpassword"));
            $this->assertTrue($mgr->isCorrectPassword("jsmith", "validpassword"));
            $this->assertTrue($mgr->isCorrectPassword("jdoe", "nonvalidpassword"));
            $this->assertTrue($mgr->isCorrectPassword("jsmith", "nonvalidpassword"));
            $this->assertTrue($mgr->isCorrectPassword("hans", "validpasswor"));
    }

This test has a few issues:

- First, it assert a precondition to apply : A database must exist with the users jdoe and jsmith and the credentials
  must match the ones provided in the test
- There are a lot of dependencies in this code, almost the complete Authentication code must exists. Our test
  will fail if this code changes or contains bugs, even the isCorrectPassword method is correct.

### Reducing dependencies

To avoid these issues, you need to code your classes with testing in mind. Maybe now you're screaming *"Tests shouldn't
affect my code, just test if it is correct!"*, but it's not that easy. Testability should be considered as a quality aspect
of your code, just like commenting, keeping functions coherent, etc. Non-testable code should be considered as a bug, or
at least as a design-flaw.

One big buzzword in development is now Inversion of Control and Dependency Injection. You can google the details, but in
our case it basically means: Instead of your class (in this case UserManager) setting up it's dependencies (creating an Authentication Manager and
then fetching users from it), the dependencies are given to the class. This has the advantage that you can mock the
dependencies in your testclasses which heavily reduces test-complexity. On the downside this can lead to more complicate
Api's, as you have to know the dependencies of your Object when creating it. Therefore we often allow to provide an
dependency from the outside (when testing), but normally create the dependencies when nothing is provided (normal use).

In our case we could say that we allow our UserManager to use a provided set of Users instead of fetching it from the
Authmanger:

    class UserManager
    {

        public function __construct($config, $authMgr = null)
        {
            if ($authMgr == null) {
                // no Authmanager provided, resolve dependency by yourself
                $this->authMgr = new AuthManager($config);
            } else {
                $this->authMgr = $authMgr;
            }
        }
    }

It would of course be best to create an Interface like UserSource which the AuthManger implements, but in this example
we trust our Programmer to provide a suitable object. We now can eliminate all the AuthManager dependencies by mocking the
AuthManager (lets dumb it down to just providing an array of users):

    use Icinga/MyLibrary/UserManager

    class AuthManagerMock
    {
        public $users;

        /**
        *   Create a new mock classw with the provided users and their credentials
        *
        *   @param array $userPasswordCombinations  The users and password combinations to use
        **/
        public function __construct(array $userPasswordCombinations)
        {
            $this->users = $userPasswordCombinations;
        }

        public function getUsers()
        {
            return $this->users;
        }
    }

    class UserManagerTest extends \PHPUnit_Framework_TestCase
    {
        /**
        *   Test whether an user is correctly recognized by the UserManager
        *
        **/
        public function testUserManager()
        {
            $authMock = new AuthManagerMock(array(
                "jdoe" => "validpassword",
                "jsmith" => "validpassword"
            ));
            $mgrConfg = new \Zend_Config(array(), $authMock);
            $mgr = new UserManager($mgrConfig);

            $this->assertTrue($mgr->isCorrectPassword("jdoe", "validpassword"));
            $this->assertTrue($mgr->isCorrectPassword("jsmith", "validpassword"));
            $this->assertFalse($mgr->isCorrectPassword("jdoe", "nonvalidpassword"));
            $this->assertFalse($mgr->isCorrectPassword("jsmith", "nonvalidpassword"));
            $this->assertFalse($mgr->isCorrectPassword("hans", "validpassword"));
    }

Ok, we might have more code here than before, but our test is now less like prone to fail:

- Our test doesn't assume any preconditions to apply, like having a db server with correct users



### Splitting up assertions

The test is now not that bad, but still has a few issues:

- If an assert fails, we don't know which one, as the message will be rather generic ("failed asserting that False is True")
- In this case it might be obvious what we test, but if someone sees the class and the assertions, he doesn't know what
assumptions are made

To fix those issues, we have to split up our big test method in several smaller one and give the testmethod **talking names**.
Also, the assertions should get an error message that will be printed on failure.

    /**
    *   Testcases for the UserManager class
    *
    **/
    class UserManagerTest extends \PHPUnit_Framework_TestCase
    {
        /**
        *   Creates a new UserManager with a mocked AuthManage
        *
        *   @returns UserManager
        **/
        public function getUserManager()
        {
            $authMock = new AuthManagerMock(array(
                "jdoe" => "validpassword",
                "jsmith" => "validpassword"
            ));
            $mgrConfg = new \Zend_Config(array(), $authMock);
            return new UserManager($mgrConfig);
        }

        /**
        *  Tests whether correct user/name combinations are considered valid
        *
        **/
        public function testCorrectUserPasswordCombinations()
        {
            $mgr = $this->getUserManager();
            $this->assertTrue(
                $mgr->isCorrectPassword("jdoe", "validpassword"),
                "Asserted that a correct user/password combination is considered valid for jdoe"
            );
            $this->assertTrue(
                $mgr->isCorrectPassword("jsmith", "validpassword"),
                "Asserted that a correct user/password combination is considered valid for jsmith"
            );
        }

        /**
        *  Tests whether invalid names are rejected
        *
        **/
        public function testInvalidUsernameRecognition()
        {
            $mgr = $this->getUserManager();
            $this->assertFalse(
                $mgr->isCorrectPassword("hans", "validpassword"),
                "Asserted a non-existing user to be be considered invalid"
            );
        }

        /**
        *  Tests whether invalid passwords for existing users are rejected
        *
        **/
        public function testInvalidPasswordRecognition()
        {
            $mgr = $this->getUserManager();
            $this->assertFalse(
                $mgr->isCorrectPassword("jsmith", "nonvalidpassword"),
                "Asserted that an invalid password for an existing user is considered invalid"
            );
        }
    }

Now if something fails, we now see what has been tested via the testmethod and what caused the test to fail in the
assertion error message. You could also leave the comments and everybody knows what you are doing.
