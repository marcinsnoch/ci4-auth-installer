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
