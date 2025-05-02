# CodeIgniter 4 Auth Installer

Automatyczny instalator integracji **AUTH** z frameworkiem **CodeIgniter 4**.

---
# Auth system

`app/Config/Filters.php`

```php
public array $aliases = [
    ...
    'auth'          => \App\Filters\Auth::class,
    'noauth'        => \App\Filters\NoAuth::class,
];
```

`app/Config/Routes.php`

```php
$routes->addRedirect('home', '/');
$routes->get('/', 'HomeController::index', ['filter' => 'auth']);
$routes->get('/terms-and-conditions', 'TermsController::index');
$routes->get('/logout', 'AuthController::logout');
$routes->match(['get', 'post'], 'login', 'AuthController::login', ['filter' => 'noauth']);
$routes->match(['get', 'post'], 'register', 'AuthController::register', ['filter' => 'noauth']);
$routes->match(['get', 'post'], 'forgot-password', 'AuthController::forgotPassword', ['filter' => 'noauth']);
$routes->match(['get', 'post'], 'reset-password', 'AuthController::resetPassword', ['filter' => 'noauth']);
$routes->get('activation', 'AuthController::activation', ['filter' => 'noauth']);
```

`app/Config/Validation.php`

```php
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
```

`app/Controllers/AuthController.php`

```php
<?php

namespace App\Controllers;

use App\Libraries\EmailService;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class AuthController extends BaseController
{
    private $alertMsg = null;

    public function login()
    {
        if ($this->checkRememberMe()) {
            return redirect()->to('home');
        }

        if ($this->isFormSubmitted('login') && $this->validate('login')) {
            $user = $this->getUserByEmail($this->request->getPost('email'));

            if (!$user || !$this->isAccountActive($user) || !$this->isPasswordValid($user)) {
                alertError($this->alertMsg ?? lang('Auth.alert.incorrect_login'));
                return redirect()->to('login');
            }

            $this->rememberMe($user->id);
            $this->setUserSession($user);
            return redirect()->to('home')->withInput();
        }

        $this->twig->display('auth/login');
    }

    public function forgotPassword()
    {
        if ($this->isFormSubmitted('send') && $this->validate('user_email')) {
            $user = $this->getUserByEmail($this->request->getPost('email'));
            $user->token = bin2hex(random_bytes(64));
            $user->save();

            (new EmailService($this->twig))->sendResetPasswordEmail($user);

            alertSuccess(lang('Auth.alert.reset_email_send'));
            return redirect()->to('login');
        }

        $this->twig->display('auth/forgot_password');
    }

    public function resetPassword()
    {
        $token = $this->request->getGet('token');
        $user = UserModel::where('token', $token)->whereNotNull('token')->first();

        if (!$user) {
            return redirect()->to('login');
        }

        if ($this->isFormSubmitted('reset_password') && $this->validate('reset_password')) {
            $user->password = password_hash($this->request->getPost('new_password'), PASSWORD_DEFAULT);
            $user->token = null;
            $user->save();

            (new EmailService($this->twig))->sendPasswordChangedEmail($user);

            alertSuccess(lang('Auth.alert.password_changed'));
            return redirect()->to('login');
        }

        $this->twig->display('auth/reset_password', ['token' => $user->token]);
    }

    public function logout()
    {
        session()->destroy();
        delete_cookie('remember_token');
        UserModel::where('id', session()->id)->update(['remember_token' => null]);

        return redirect()->to('login')->withInput();
    }

    public function register()
    {
        if (!$this->appConfig->register) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($this->isFormSubmitted('register') && $this->validate('register')) {
            $user = UserModel::create([
                'first_name' => $this->request->getPost('first_name'),
                'last_name' => $this->request->getPost('last_name'),
                'email' => $this->request->getPost('email'),
                'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
                'terms' => (bool) $this->request->getPost('terms'),
                'token' => bin2hex(random_bytes(64)),
            ]);

            (new EmailService($this->twig))->sendActivationEmail($user);

            alertSuccess(lang('Auth.alert.registered_successfully'));
            return redirect()->to('login');
        }

        $this->twig->display('auth/register');
    }

    public function activation()
    {
        $token = $this->request->getGet('token');
        if (!$token) {
            throw PageNotFoundException::forPageNotFound();
        }

        $user = UserModel::where('token', $token)->first();

        if (!$user) {
            alertError(lang('Auth.alert.some_error'));
        } else {
            $user->update(['token' => null]);
            alertSuccess(lang('Auth.alert.activated_successfull'));

            (new EmailService($this->twig))->sendConfirmationEmail($user);
        }

        return redirect()->to('login');
    }

    // ======================
    // PRIVATE HELPER METHODS
    // ======================

    private function isFormSubmitted(string $field): bool
    {
        return $this->request->getPost($field) !== null;
    }

    private function getUserByEmail(string $email)
    {
        return UserModel::where('email', $email)->first();
    }

    private function isAccountActive($user): bool
    {
        if ($user->token !== null) {
            $this->alertMsg = lang('Auth.alert.account_not_activated');
            return false;
        }
        return true;
    }

    private function isPasswordValid($user): bool
    {
        return password_verify($this->request->getPost('password'), $user->password);
    }

    private function checkRememberMe(): bool
    {
        $cookie = get_cookie('remember_token');
        if (!$cookie) {
            return false;
        }

        $user = UserModel::where('remember_token', $cookie)->first();
        if (!$user) {
            return false;
        }

        $this->setUserSession($user);
        return true;
    }

    private function setUserSession(object $user): void
    {
        session()->set([
            'id' => $user->id,
            'name' => $user->full_name,
            'email' => $user->email,
            'isLoggedIn' => true,
        ]);
    }

    private function rememberMe(int $id): void
    {
        $user = UserModel::find($id);
        $token = bin2hex(random_bytes(64));
        set_cookie('remember_token', $token, 60 * 60 * 24 * 31); // 31 days
        $user->remember_token = $token;
        $user->save();
    }
}

```

`app/Database/Migrations/_CreateUsersTable.php`

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
            ],
            'last_name' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
            ],
            'is_admin' => [
                'type' => 'TINYINT',
                'constraint' => 3,
                'unsigned' => true,
                'null' => false,
                'default' => '0',
            ],
            'remember_token' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'terms' => [
                'type' => 'TINYINT',
                'constraint' => '1',
                'default' => false,
            ],
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'last_activity' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email', 'email');
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users', true);
    }
}
```

`auth/Database/UserSeeder.php`

```php
<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'email' =>          'admin@admin.pl',
                'password' =>       '$2y$10$vBqB8ef.o3XtJPsApSFyqekP4k8sEGCx0/9VMbgTcwp9Fo3lR6/lm', // password
                'first_name' =>     'Marcin',
                'last_name' =>      'Snoch',
                'is_admin' =>       1,
                'remember_token' => null,
                'terms' =>          1,
                'token' =>          null,
                'last_activity' =>  null,
            ],
            [
                'email' =>          'user@user.pl',
                'password' =>       '$2y$10$vBqB8ef.o3XtJPsApSFyqekP4k8sEGCx0/9VMbgTcwp9Fo3lR6/lm', // password
                'first_name' =>     'User',
                'last_name' =>      'user',
                'is_admin' =>       0,
                'remember_token' => null,
                'terms' =>          0,
                'token' =>          null,
                'last_activity' =>  null,
            ],
        ];
        for ($i = 0; $i < count($users); ++$i) {
            $this->db->table('users')->insert($users[$i]);
        }
    }
}
```

Reset database

```bash
php spark migrate:rollback -f
```

```bash
php spark migrate && php spark db:seed AllSeeders
```

`app/Filters/Auth.php`

```php
<?php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class Auth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here
    }
}
```

`app/Filters/NoAuth.php`

```php
<?php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class NoAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('dashboard');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here
    }
}
```

`app/Language/pl/Auth.php`

```php
'alert' => [
    'incorrect_login' => 'Login lub hasło są niepoprawne!',
    'reset_email_send' => 'Na podany adres email wyślemy link do resetowania hasła.',
    'password_changed' => 'Hasło zostało zmienione! Możesz się zalogować.',
    'registered_successfully' => 'Twoje konto zostało pomyślnie utworzone.<br> Wysłaliśmy Ci wiadomość e-mail z potwierdzeniem.',
    'some_error' => 'Wystąpił nieoczekiwany błąd!',
    'activated_successfull' => 'Twoje konto zostało pomyślnie aktywowane.<br> Wysłaliśmy Ci wiadomość e-mail z potwierdzeniem.',
    'account_not_activated' => 'Konto jest nie aktywne lub zablokowane.',
],
```

`app/Language/pl/Email.php`

```php
<?php

return [
    'greeting' => 'Witaj {0}!',
    'footer' => 'Ta wiadomość została wysłana z <a href="{0}">{1}</a>.',
    'activation' => [
        'subject' => 'Aktywuj swoje konto',
        'msg' => 'Kliknij poniższy link, aby aktywować swoje konto:',
        'btn' => 'Aktywuj konto',
    ],
    'confirmation' => [
        'subject' => 'Konto zostało pomyślnie aktywowane',
        'msg' => 'Twoje konto zostało pomyślnie aktywowane!',
    ],
    'password_changed' => [
        'subject' => 'Twoje hasło zostało zmienione',
        'msg' => 'Twoje hasło zostało zmienione!',
    ],
    'reset_password' => [
        'subject' => 'Prośba o zresetowanie hasła',
        'msg' => 'Otrzymaliśmy prośbę o zmianę hasła. Kliknij poniższy link, aby zresetować hasło.',
        'btn' => 'Resetuj hasło',
    ],
];
```

`app/Libraries/EmailService.php`

```php
<?php

namespace App\Libraries;

use CodeIgniter\Email\Email;

class EmailService
{
    protected Email $email;
    protected $twig;

    public function __construct($twig = null)
    {
        $this->email = \Config\Services::email();
        $this->twig = $twig;
    }

    /**
     * Send activation email to user.
     */
    public function sendActivationEmail(object $user): bool
    {
        $message = $this->render('emails/activation', ['user' => $user]);
        $subject = lang('Email.activation.subject');

        return $this->send($user->email, $subject, $message);
    }

    /**
     * Send reset password email.
     */
    public function sendResetPasswordEmail(object $user): bool
    {
        $message = $this->render('emails/reset_password', ['user' => $user]);
        $subject = lang('Email.reset_password.subject');

        return $this->send($user->email, $subject, $message);
    }

    /**
     * Send confirmation after password change.
     */
    public function sendPasswordChangedEmail(object $user): bool
    {
        $message = $this->render('emails/password_changed', ['user' => $user]);
        $subject = lang('Email.password_changed.subject');

        return $this->send($user->email, $subject, $message);
    }

    /**
     * Send final email after account activation.
     */
    public function sendConfirmationEmail(object $user): bool
    {
        $message = $this->render('emails/confirmation', ['user' => $user]);
        $subject = lang('Email.confirmation.subject');

        return $this->send($user->email, $subject, $message);
    }

    /**
     * Core email sending logic.
     */
    protected function send(string $to, string $subject, string $message): bool
    {
        $this->email->clear();
        $this->email->setTo($to);
        $this->email->setFrom(config('Email')->fromEmail, config('Email')->fromName);
        $this->email->setSubject($subject);
        $this->email->setMessage($message);

        return $this->email->send();
    }

    /**
     * Render email body with Twig.
     */
    protected function render(string $template, array $data): string
    {
        if (!$this->twig) {
            throw new \RuntimeException("Twig renderer not available.");
        }

        return $this->twig->render($template, $data);
    }
}
```

`.env`

```ini#--------------------------------------------------------------------
# EMAIL
#--------------------------------------------------------------------

email.fromEmail = "no-reply@yourdomain.com"
email.fromName = "Your App"
email.protocol = smtp
email.SMTPHost = 127.0.0.1
email.SMTPPort = 25
email.SMTPCrypto = ''
email.SMTPTimeout = 30
email.mailType = html
```


`app/Models/UserModel.php`

```php
protected $fillable = ['id', 'first_name', 'last_name', 'email', 'password', 'token', 'terms', 'created_at', 'updated_at'];
```

`app/Views/emails/activation.twig`

```twig
<h1>{{ lang('Email.greeting', [user.first_name]) }}</h1><hr>
<p>{{ lang('Email.activation.msg') }}</p>
<p><a href="{{ site_url('activation?token=' ~ user.token) }}">{{ lang('Email.activation.btn') }}</a></p>
<p><small>{{ lang('Email.footer', [site_url(), config.appName]) }}</small></p>
```

`app/Views/emails/confirmation.twig`

```twig
<h1>{{ lang('Email.greeting', [user.first_name]) }}</h1><hr>
<p>{{ lang('Email.confirmation.msg') }}</p>
<p><small>{{ lang('Email.footer', [site_url(), config.appName]) }}</small></p>
```

`app/Views/emails/password_changed.twig`

```twig
<h1>{{ lang('Email.greeting', [user.first_name]) }}</h1><hr>
<p>{{ lang('Email.password_changed.msg') }}</p>
<p><small>{{ lang('Email.footer', [site_url(), config.appName]) }}</small></p>
```

`app/Views/emails/reset_password.twig`

```twig
<h1>{{ lang('Email.greeting', [user.first_name]) }}</h1><hr>
<p>{{ lang('Email.reset_password.msg') }}</p>
<p><a href="{{ site_url('reset-password?token=' ~ user.token) }}">{{ lang('Email.reset_password.btn') }}</a></p>
<p><small>{{ lang('Email.footer', [site_url(), config.appName]) }}</small></p>
```

