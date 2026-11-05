<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    protected $signature = 'erp:create-user {email : Email đăng nhập} {--name= : Tên hiển thị}';

    protected $description = 'Tạo tài khoản đăng nhập (mật khẩu được hỏi trực tiếp, không lưu trong lịch sử lệnh).';

    public function handle(): int
    {
        $password = (string) $this->secret('Mật khẩu (tối thiểu 12 ký tự)');

        $validator = Validator::make(
            ['email' => $this->argument('email'), 'password' => $password],
            ['email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'string', 'min:12', 'max:255']],
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $this->option('name') ?: $this->argument('email'),
            'email' => $this->argument('email'),
            'password' => $password, // cast 'hashed' của model tự băm
        ]);
        $this->info('Đã tạo tài khoản '.$this->argument('email'));

        return self::SUCCESS;
    }
}
