<?php

use App\Model\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $temp = new Template;
        $temp->template_name = "Invoice 1";
        $temp->module = "invoice";
        $temp->template_image = url('public/uploads/templates/invoicennew.jpg');
        $temp->file_name = "invoice";
        $temp->is_default = 1;
        $temp->save();

        $temp = new Template;
        $temp->template_name = "Invoice 2";
        $temp->module = "invoice";
        $temp->template_image = url('public/uploads/templates/lite.jpg');
        $temp->file_name = "invoice_lite";
        $temp->is_default = 0;
        $temp->save();

        $temp = new Template;
        $temp->template_name = "Customer";
        $temp->module = "customer";
        $temp->template_image = url('public/uploads/templates/customer-1603238400-1603297399.jpeg');
        $temp->file_name = "customer";
        $temp->is_default = 1;
        $temp->save();

        $temp = new Template;
        $temp->template_name = "Credit Note";
        $temp->module = "credit_note";
        $temp->template_image = url('public/uploads/templates/credit-note-1603238400-1603297438.jpeg');
        $temp->file_name = "credit_note";
        $temp->is_default = 1;
        $temp->save();

        $temp = new Template;
        $temp->template_name = "Delivery";
        $temp->module = "delivery";
        $temp->template_image = url('public/uploads/templates/delivery-1603238400-1603297467.jpeg');
        $temp->file_name = "delivery";
        $temp->is_default = 1;
        $temp->save();
    }
}
