<?php


namespace Bytes\UserBundle\Tests\Fixtures\Models;


use Bytes\Common\Faker\Providers\MiscProvider;
use Bytes\UserBundle\Entity\CommandUserInterface;
use Bytes\UserBundle\Entity\CommandUserTrait;
use Exception;
use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Faker\Provider\Address;
use Faker\Provider\Barcode;
use Faker\Provider\Biased;
use Faker\Provider\Color;
use Faker\Provider\Company;
use Faker\Provider\DateTime;
use Faker\Provider\File;
use Faker\Provider\HtmlLorem;
use Faker\Provider\Image;
use Faker\Provider\Internet;
use Faker\Provider\Lorem;
use Faker\Provider\Medical;
use Faker\Provider\Miscellaneous;
use Faker\Provider\Payment;
use Faker\Provider\Person;
use Faker\Provider\PhoneNumber;
use Faker\Provider\Text;
use Faker\Provider\UserAgent;
use Faker\Provider\Uuid;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use function Symfony\Component\String\u;

/**
 * Class User
 * @package Bytes\UserBundle\Tests\Fixtures\Models
 */
class User implements CommandUserInterface, PasswordAuthenticatedUserInterface
{
    use CommandUserTrait;

    /**
     * User constructor.
     * @param string $username
     * @param string $email
     * @param string $password
     * @param array $roles
     */
    public function __construct(private string $username = '', private string $email = '', private string $password = '', array $roles = [])
    {
    }

    /**
     * @param string|null $username
     * @param string|null $email
     * @param string|null $password
     * @param array|null $roles
     * @return static
     * @throws Exception
     */
    public static function random(?string $username = null, ?string $email = null, ?string $password = null, ?array $roles = null): static
    {
        $faker = static::getFaker();
        $static = new static($username ?? $faker->userName(), $email ?? $faker->email(),
            $password ?? $faker->randomAlphanumericString());
        return $static->setRoles($roles ?? $faker->words(3));
    }

    /**
     * @return FakerGenerator|MiscProvider|Address|Barcode|Biased|Color|Company|DateTime|File|HtmlLorem|Image|Internet|Lorem|Medical|Miscellaneous|Payment|Person|PhoneNumber|Text|UserAgent|Uuid
     */
    private static function getFaker()
    {
        $faker = Factory::create();
        $faker->addProvider(new MiscProvider($faker));

        return $faker;
    }

    /**
     * @param string|null $username
     * @param string|null $email
     * @param string|null $password
     * @param array|null $roles
     * @return $this
     * @throws Exception
     */
    public function randomize(?string $username = null, ?string $email = null, ?string $password = null, ?array $roles = null)
    {
        $faker = static::getFaker();
        $this->setUsername($username ?? $faker->userName());
        $this->setEmail($email ?? $faker->email());
        $this->setPassword($password ?? $faker->randomAlphanumericString());
        $this->setRoles($roles ?? $faker->words(3));

        return $this;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @param array $roles
     * @return $this
     */
    public function setRoles(array $roles): static
    {
        foreach ($roles as $index => $role)
        {
            $role = u($role);
            if(!$role->startsWith('ROLE_')) {
                $roles[$index] = $role->prepend('Role_')->snake()->upper()->toString();
            }
        }
        $this->roles = $roles;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return string|null
     */
    public function getSalt(): ?string
    {
        return static::getFaker()->optional()->randomAlphanumericString();
    }

    /**
     *
     */
    public function eraseCredentials()
    {
    }

    /**
     * Returns the identifier for this user (e.g. its username or e-mail address)
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }
}