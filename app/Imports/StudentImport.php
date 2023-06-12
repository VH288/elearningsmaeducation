<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class StudentImport
{
    private $successCount = 0;
    private $failureCount = 0;
    private $errors = [];

    public function import($file)
    {
        $spreadsheet = IOFactory::load($file);

        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        $headerRow = $rows[0];
        $dataRows = array_slice($rows, 1);

        foreach ($dataRows as $row) {
            $rowData = array_combine($headerRow, $row);

        $birthDateValue = $rowData['birth_date'];
        $birthDate = Carbon::createFromFormat('d/m/Y', $birthDateValue);
        $rowData['birth_date'] = $birthDate->format('d/m/Y');

        $birthDateRule = $this->rules()['birth_date'];
        unset($this->rules()['birth_date']);

        $validator = Validator::make($rowData, $this->rules());

        $this->rules()['birth_date'] = $birthDateRule;

        if ($validator->fails()) {
            $this->addError($rowData, $validator->errors()->first());
            continue;
        }
        $today = Carbon::now();
        $birthdatecheck=Carbon::parse($birthDateValue);
        if($birthdatecheck->gt($today) || $birthdatecheck->eq($birthdatecheck)){
            $this->addError($rowData, "Birth date cannot be future or present");
            continue;
        }

            $user = User::create([
                'username' => $rowData['nis'],
                'password' => bcrypt(Carbon::parse($rowData['birth_date'])->format('dmY')),
                'email' => $rowData['nis'] . '@education.com',
                'user_role_id' => 3,
            ]);

            $student = Student::create([
                'name' => $rowData['name'],
                'pet_name' => $rowData['pet_name'],
                'gender' => $rowData['gender'],
                'birth_place' => $rowData['birth_place'],
                'birth_date' => Carbon::parse($rowData['birth_date'])->format('Y-m-d H:i:s'),
                'religion' => $rowData['religion'],
                'address' => $rowData['address'],
                'nis' => $rowData['nis'],
                'user_id' => $user->id,
            ]);

            $this->successCount++;
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|regex:/^[A-Za-z ]+$/',
            'pet_name' => 'required|regex:/^[A-Za-z ]+$/',
            'gender' => 'required|in:Male,Female',
            'birth_place' => 'required|regex:/^[A-Za-z ]+$/',
            'birth_date' => 'required|date_format:d/m/Y',
            'religion' => 'required|in:Islam,Christian,Catholic,Hindu,Buddhist',
            'address' => 'required',
            'nis' => 'required|unique:students,nis',
        ];
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getFailureCount()
    {
        return $this->failureCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    private function addError(array $row, $errors)
    {
        $this->failureCount++;

        $this->errors[] = [
            'row' => $row,
            'errors' => $errors,
        ];
    }
}
