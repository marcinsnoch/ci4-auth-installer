<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------
    public array $login = [
        'email' => [
            'label' => 'Auth.Email',
            'rules' => 'required|min_length[6]|max_length[50]|valid_email',
        ],
        'password' => [
            'label' => 'Auth.Password',
            'rules' => 'required|min_length[8]|max_length[255]',
        ],
    ];
    public array $register = [
        'first_name' => [
            'label' => 'Auth.First_name',
            'rules' => 'required|min_length[3]|max_length[50]'
        ],
        'last_name' => [
            'label' => 'Auth.Last_name',
            'rules' => 'required|min_length[3]|max_length[50]'
        ],
        'email' => [
            'label' => 'Auth.Email',
            'rules' => 'required|min_length[6]|max_length[50]|valid_email|is_unique[users.email]',
        ],
        'password' => [
            'label' => 'Auth.Password',
            'rules' => 'required|min_length[8]|max_length[255]',
        ],
        'confirm_password' => [
            'label' => 'Auth.Confirm_password',
            'rules' => 'required|matches[password]',
        ],
    ];
    public array $user_email = [
        'email' => [
            'label' => 'Auth.Email',
            'rules' => 'required|min_length[6]|max_length[50]|valid_email',
        ],
    ];
    public array $reset_password = [
        'new_password' => [
            'label' => 'Auth.New_password',
            'rules' => 'required|min_length[8]|max_length[255]',
        ],
        'confirm_password' => [
            'label' => 'Auth.Confirm_password',
            'rules' => 'required|matches[new_password]',
        ],
    ];

    public array $update_profile = [
        'first_name' => [
            'label' => 'UserProfile.First_name',
            'rules' => 'required|min_length[3]|max_length[20]',
        ],
        'last_name' => [
            'label' => 'UserProfile.Last_name',
            'rules' => 'required|min_length[3]|max_length[20]',
        ],
    ];

    public array $update_password = [
        'password' => [
            'label' => 'UserProfile.Password',
            'rules' => 'required|min_length[8]|max_length[255]',
        ],
        'confirm_password' => [
            'label' => 'UserProfile.Confirm_password',
            'rules' => 'required|matches[password]',
        ],
    ];

    public array $product = [
        'name' => [
            'label' => 'Product.Name',
            'rules' => 'required|min_length[1]|max_length[255]',
        ],
        'price' => [
            'label' => 'Product.Price',
            'rules' => 'required|decimal',
        ],
        'description' => [
            'label' => 'Product.Description',
            'rules' => 'permit_empty|min_length[1]|max_length[255]',
        ],
    ];
}
