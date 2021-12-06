# Using the Dependency Injection
- Dependency injection is a **software design pattern** via which **one or more dependencies** are **injected or passed** by reference **into an object**.
- What this **exactly** means on a **practical level** is shown in the following **two simple examples**:
1. Here, you will see a **simplified PHP example**, where the `$database` **object is created** in the `getTotalCustomers` method.
    ```
    public function getTotalCustomers()
    {
        $database = new \PDO( … );
        $statement = $database->query( 'SELECT ...' );
        return $statement->fetchColumn();
    }
    ```
- This means that the **dependency on the database object** is being **locked** in an **object instance method**.
- This makes for **tight coupling**, which has **several disadvantages** such as **reduced reusability** and a **possible system-wide effect** caused by **changes made to some parts of the code**.
- A **solution to this problem** is to **avoid methods with these sorts of dependencies** by **injecting a dependency into a method**, as follows:
2. Here, a `$database` **object is passed (injected)** into a method.
    ```
    public function getTotalCustomers( $database )
    {
        $statement = $database->query( 'SELECT ...' );
        return $statement->fetchColumn();
    }
    ```
- That's all that dependency injection is—a **simple concept** that **makes code loosely coupled**.
- While the concept is simple, it may not be easy to implement it across large platforms such as Magento.
- Magento has its **own object manager and dependency injection mechanism**.
- Run the following **set of commands** on the console within Magento's root directory:
    ```
    php bin/magento module:enable Foggyline_Di
    php bin/magento setup:upgrade
    php bin/magento foggy:di 
    ```
- When `php bin/magento foggy:di` is run, it will run the code within the `execute` **method** in the `DiTestCommand` **class**.
- Therefore, we can use the `__construct` and `execute` **methods** from within the `DiTestCommand` **class** and the `di.xml` file itself as a **playground for DI**.
## The Object Manager
- The **initializing of objects** in Magento is done via what is called **object manager**.
- The **object manager** itself is **an instance of** the `Magento\Framework\ObjectManager\ObjectManager` **class** that **implements** the `Magento\Framework\ObjectManagerInterface` **class**.
- The `ObjectManager` **class** defines the following **three methods**:
    - **create( $type, array $arguments = [] )**: This **creates** a **new object instance**
    - **get( $type )**: This **retrieves** a **cached object instance**
    - **configure( array $configuration )**: This **configures** the `di` **instance**
- The **object manager** can **instantiate a PHP class**, which can be a **model**, **helper**, or **block** object.
- Unless the **class** that we working with has **already received** an **instance of the object manager**, we can receive it by passing `ObjectManagerInterface` into the **class constructor**, as follows:
    ```
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->_objectManager = $objectManager;
    }
    ```
- Though we can still use plain old PHP to instantiate an object such as `$object = new \Foggyline\Di\Model\Object()`, by using the **object manager**, we can take **advantage of Magento's advanced object features** such as **automatic constructor dependency injection** and **object proxying**.
- The **object manager's** `create` **method** always returns a **new object instance**, while the `get` **method** returns a **singleton**.
## Dependency Injection
- Until now, we have seen how the **object manager** has **control over** the **instantiation of dependencies**.
- However, **by convention**, the **object manager isn't supposed to be used directly** in Magento. Rather, it should be used for **system-level things** that **bootstrap** Magento.
- We are **encouraged to use** the module's `etc/di.xml` file to instantiate objects.
- Let's **dissect** one of the existing `di.xml` entries, such as the one found under the `vendor/magento/module-admin-notification/etc/adminhtml/di.xml` file for the `Magento\Framework\Notification\MessageList` type:
    ```
    <type name="Magento\Framework\Notification\MessageList">
        <arguments>
            <argument name="messages" xsi:type="array">
                <item name="baseurl" xsi:type="string">Magento\AdminNotification\Model\System\Message\Baseurl</item>
                <item name="security" xsi:type="string">Magento\AdminNotification\Model\System\Message\Security</item>
                <item name="cacheOutdated" xsi:type="string">Magento\AdminNotification\Model\System\Message\CacheOutdated</item>
                <item name="media_synchronization_error" xsi:type="string">Magento\AdminNotification\Model\System\Message\Media\Synchronization\Error</item>
                <item name="media_synchronization_success" xsi:type="string">Magento\AdminNotification\Model\System\Message\Media\Synchronization\Success</item>
            </argument>
        </arguments>
    </type>
    ```
- Basically, what this means is that **whenever an instance of** `Magento\Framework\Notification\MessageList` is being created, the `messages` **parameter is passed on to the constructor**.
- The `messages` **parameter** is being defined as an **array**, which further consists of other **string type items**.
- In this case, **values of these string type attributes** are **class names**, as follows:
    ```
    Magento\Framework\ObjectManager\ObjectManager
    Magento\AdminNotification\Model\System\Message\Baseurl
    Magento\AdminNotification\Model\System\Message\Security
    Magento\AdminNotification\Model\System\Message\CacheOutdated
    Magento\AdminNotification\Model\System\Message\Media\Synchronization\Error
    Magento\AdminNotification\Model\System\Message\Media\Synchronization\Success
    ```
- If you now take a look at the **constructor** of `MessageList`, you will see that it is defined in the following way:
    ```
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $messages = []
    )
    {
        //Method body here...
    }
    ```
- Magento **collects** the **DI definitions** from **across entire the platform** and **merges** them.
- Ideally, **we would not pass** the **class type** but **interface** into the PHP constructor and then **set the type** in `di.xml`.
- This is where the `type`, `preference` and `virtualType` **play a major role** in `di.xml`.
## Configuring class preferences
- A **great number** of Magento's **core classes** pass **interfaces** around constructors.
- The **benefit** of this is that the **object manager**, with the help of `di.xml`, can decide **which class to actually instantiate for a given interface**.
1. Let's **imagine** the `Foggyline\Di\Console\Command\DiTestCommand` **class** with a constructor, as follows:
    ```
    public function __construct(
        \Foggyline\Di\Model\TestInterface $myArg1,
        $myArg2,
        $name = null
    )
    {
        //Method body here...
    }
    ```
- Note how `$myArg1` is **type hinted** as the `\Foggyline\Di\Model\TestInterface` **interface**.
- The **object manager** knows that it **needs to look into** the **entire** `di.xml` for possible `preference` definitions.
- We can define `preference` within the module's `di.xml` file, as follows:
    ```
    <preference for="Foggyline\Di\Model\TestInterface" type="Foggyline\Di\Model\Cart"/>
    ```
- Here, we are basically saying that when someone ask for **an instance of** `Foggyline\Di\Model\TestInterface`, give it **an instance of** the `Foggyline\Di\Model\Cart` **object**.
- For this to work, the `Cart` **class** has to **implement** `TestInterface` itself.
- Once the **preference definition** is in place, `$myArg1` shown in the preceding example becomes **an object of** the `Cart` **class**.
2. Now, let's have a look at the `Foggyline\Di\Console\Command\DiTestCommand` **class** with a constructor:
    ```
    public function __construct(
        \Foggyline\Di\Model\User $myArg1,
        $myArg2,
        $name = null
    ) 
    {
        //Method body here...
    }
    ```
- Note how `$myArg1` is now **type hinted** as the `\Foggyline\Di\Model\User` **class**.
- Like in the previous example, the **object manager** will look into `di.xml` for possible `preference` definitions.
- Let's define the `preference` element within the module's `di.xml` file, as follows:
    ```
    <preference for="Foggyline\Di\Model\User" type="Foggyline\Di\Model\Cart"/>
    ```
- What this `preference` definition is saying is that whenever **an instance of** the `User` **class** is requested, pass **an instance of** the `Cart` **object**.
- This will **work only if** the `Cart` **class** extends from `User`.
- This is a **convenient way** of **rewriting a class**, where the **class** is being **passed directly** into **another class constructor** in place of the **interface**.
## Using virtual types
- Along with `type` and `preference`, there is another **powerful feature** of `di.xml` that we can use.
- The `virtualType` element enables us to define **virtual types**.
- Creating a **virtual type** is like creating a **subclass of an existing class** except for the fact that it's done in `di.xml` and **not in code**.
- **Virtual types** are a **way of injecting dependencies** into some of the existing classes **without affecting other classes**.
- To explain this via a **practical example**, let's take a look at the following **virtual type** defined in the `app/etc/di.xml` file.
    ```
    <virtualType name="Magento\Framework\Message\Session\Storage" type="Magento\Framework\Session\Storage">
        <arguments>
            <argument name="namespace" xsi:type="string">message</argument>
        <arguments>
    </virtualType>
    <type name="Magento\Framework\Message\Session">
        <arguments>
            <argument name="storage" xsi:type="object">Magento\Framework\Message\Session\Storage</argument>
        <arguments>
    </type>
    ```
- The `virtualType` definition in the preceding example is `Magento\Framework\Message\Session\Storage`, which extends from `Magento\Framework\Session\Storage` and **overwrites** the `namespace` parameter to the `message` string value.
- In `virtualType`, the `name` attribute defines the **globally unique name** of the **virtual type**, while the `type` attribute **matches** the **real PHP class** that the **virtual type is based on**.
- Now if you look at the `type` definition, you will see that its `storage` argument is **set to the object** of `Magento\Framework\Message\Session\Storage`.
- The `Session\Storage` file is actually a **virtual type**.
- This allows `Message\Session` to be customized **without affecting other classes** that **also declare a dependency** on `Session\Storage`.
- **Virtual types** allow us to **effectively change the behavior of a dependency** when it is used in a **specific class**.
