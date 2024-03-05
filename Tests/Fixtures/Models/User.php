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
 * Class User.
 */
class User implements CommandUserInterface, PasswordAuthenticatedUserInterface
{
    use CommandUserTrait;

    /**
     * User constructor.
     */
    public function __construct(private string $username = '', private string $email = '', private string $password = '', array $roles = [])
    {
    }

    /**
     * @throws Exception
     */
    public static function random(string $username = null, string $email = null, string $password = null, array $roles = null): static
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
     * @return $this
     *
     * @throws Exception
     */
    public function randomize(string $username = null, string $email = null, string $password = null, array $roles = null)
    {
        $faker = static::getFaker();
        $this->setUsername($username ?? $faker->userName());
        $this->setEmail($email ?? $faker->email());
        $this->setPassword($password ?? $faker->randomAlphanumericString());
        $this->setRoles($roles ?? $faker->words(3));

        return $this;
    }

    /**
     * @return $this
     */
    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return $this
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return $this
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return $this
     */
    public function setRoles(array $roles): static
    {
        foreach ($roles as $index => $role) {
            $role = u($role);
            if (!$role->startsWith('ROLE_')) {
                $roles[$index] = $role->prepend('Role_')->snake()->upper()->toString();
            }
        }

        $this->roles = $roles;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getSalt(): ?string
    {
        return static::getFaker()->optional()->randomAlphanumericString();
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * Returns the identifier for this user (e.g. its username or e-mail address).
     */
    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
