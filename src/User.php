<?php

/*
 * This file is part of the Сáша framework.
 *
 * (c) tchiotludo <http://github.com/tchiotludo>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);

namespace Cawa\Oauth;

use Cawa\Date\Date;

class User
{
    /**
     * @var string
     */
    private $type;

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type) : self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @var string
     */
    private $uid;

    /**
     * @return string
     */
    public function getUid() : string
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     *
     * @return User
     */
    public function setUid(string $uid) : self
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @var string
     */
    private $username;

    /**
     * @return string
     */
    public function getUsername() : string
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username) : self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @var string
     */
    private $email;

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email) : self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @var bool
     */
    private $verified;

    /**
     * @return bool
     */
    public function isVerified()
    {
        return $this->verified;
    }

    /**
     * @param bool $verified
     *
     * @return $this
     */
    public function setVerified(bool $verified) : self
    {
        $this->verified = $verified;

        return $this;
    }

    /**
     * @var string
     */
    private $firstName;

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return User
     */
    public function setFirstName($firstName) : self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @var string
     */
    private $lastName;

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return User
     */
    public function setLastName($lastName) : self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Gender : Male
     */
    const GENDER_MALE = 'M';

    /**
     * Gender : Female
     */
    const GENDER_FEMALE = 'F';

    /**
     * @var string
     */
    private $gender;

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     *
     * @return User
     */
    public function setGender($gender) : self
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * @var Date
     */
    private $birthday;

    /**
     * @return Date
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param Date $birthday
     *
     * @return User
     */
    public function setBirthday(Date $birthday = null) : self
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * @var string
     */
    private $locale;

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale($locale) : self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @var array
     */
    private $extraData = [];

    /**
     * @return string
     */
    public function getExtraData() : array
    {
        return $this->extraData;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function setExtraData(array $data) : self
    {
        $this->extraData = $data;

        return $this;
    }

    /**
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }
}
