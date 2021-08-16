<?php

use App\Model\CurrencyMaster;
use Illuminate\Database\Seeder;

class CurrencyMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currency = new CurrencyMaster;
		$currency->symbol = "$";
		$currency->name = "US Dollar";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "USD";
		$currency->name_plural = "US dollars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "CA$";
		$currency->name = "Canadian Dollar";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "CAD";
		$currency->name_plural = "Canadian dollars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "€";
		$currency->name = "Euro";
		$currency->symbol_native = "€";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "EUR";
		$currency->name_plural = "euros";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "AED";
		$currency->name = "United Arab Emirates Dirham";
		$currency->symbol_native = "د.إ.‏";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "AED";
		$currency->name_plural = "UAE dirhams";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Af";
		$currency->name = "Afghan Afghani";
		$currency->symbol_native = "؋";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "AFN";
		$currency->name_plural = "Afghan Afghanis";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "ALL";
		$currency->name = "Albanian Lek";
		$currency->symbol_native = "Lek";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "ALL";
		$currency->name_plural = "Albanian lekë";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "AMD";
		$currency->name = "Armenian Dram";
		$currency->symbol_native = "դր.";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "AMD";
		$currency->name_plural = "Armenian drams";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "AR$";
		$currency->name = "Argentine Peso";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "ARS";
		$currency->name_plural = "Argentine pesos";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "AU$";
		$currency->name = "Australian Dollar";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "AUD";
		$currency->name_plural = "Australian dollars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "man.";
		$currency->name = "Azerbaijani Manat";
		$currency->symbol_native = "ман.";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "AZN";
		$currency->name_plural = "Azerbaijani manats";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "KM";
		$currency->name = "Bosnia-Herzegovina Convertible Mark";
		$currency->symbol_native = "KM";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "BAM";
		$currency->name_plural = "Bosnia-Herzegovina convertible marks";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Tk";
		$currency->name = "Bangladeshi Taka";
		$currency->symbol_native = "৳";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "BDT";
		$currency->name_plural = "Bangladeshi takas";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "BGN";
		$currency->name = "Bulgarian Lev";
		$currency->symbol_native = "лв.";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "BGN";
		$currency->name_plural = "Bulgarian leva";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "BD";
		$currency->name = "Bahraini Dinar";
		$currency->symbol_native = "د.ب.‏";
		$currency->decimal_digits = 3;
		$currency->rounding = 0;
		$currency->code = "BHD";
		$currency->name_plural = "Bahraini dinars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "FBu";
		$currency->name = "Burundian Franc";
		$currency->symbol_native = "FBu";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "BIF";
		$currency->name_plural = "Burundian francs";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "BN$";
		$currency->name = "Brunei Dollar";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "BND";
		$currency->name_plural = "Brunei dollars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Bs";
		$currency->name = "Bolivian Boliviano";
		$currency->symbol_native = "Bs";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "BOB";
		$currency->name_plural = "Bolivian bolivianos";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "R$";
		$currency->name = "Brazilian Real";
		$currency->symbol_native = "R$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "BRL";
		$currency->name_plural = "Brazilian reals";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "BWP";
		$currency->name = "Botswanan Pula";
		$currency->symbol_native = "P";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "BWP";
		$currency->name_plural = "Botswanan pulas";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Br";
		$currency->name = "Belarusian Ruble";
		$currency->symbol_native = "руб.";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "BYN";
		$currency->name_plural = "Belarusian rubles";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "BZ$";
		$currency->name = "Belize Dollar";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "BZD";
		$currency->name_plural = "Belize dollars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "CDF";
		$currency->name = "Congolese Franc";
		$currency->symbol_native = "FrCD";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "CDF";
		$currency->name_plural = "Congolese francs";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "CHF";
		$currency->name = "Swiss Franc";
		$currency->symbol_native = "CHF";
		$currency->decimal_digits = 2;
		$currency->rounding = 0.05;
		$currency->code = "CHF";
		$currency->name_plural = "Swiss francs";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "CL$";
		$currency->name = "Chilean Peso";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "CLP";
		$currency->name_plural = "Chilean pesos";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "CN¥";
		$currency->name = "Chinese Yuan";
		$currency->symbol_native = "CN¥";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "CNY";
		$currency->name_plural = "Chinese yuan";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "CO$";
		$currency->name = "Colombian Peso";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "COP";
		$currency->name_plural = "Colombian pesos";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "₡";
		$currency->name = "Costa Rican Colón";
		$currency->symbol_native = "₡";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "CRC";
		$currency->name_plural = "Costa Rican colóns";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "CV$";
		$currency->name = "Cape Verdean Escudo";
		$currency->symbol_native = "CV$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "CVE";
		$currency->name_plural = "Cape Verdean escudos";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Kč";
		$currency->name = "Czech Republic Koruna";
		$currency->symbol_native = "Kč";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "CZK";
		$currency->name_plural = "Czech Republic korunas";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Fdj";
		$currency->name = "Djiboutian Franc";
		$currency->symbol_native = "Fdj";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "DJF";
		$currency->name_plural = "Djiboutian francs";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Dkr";
		$currency->name = "Danish Krone";
		$currency->symbol_native = "kr";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "DKK";
		$currency->name_plural = "Danish kroner";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "RD$";
		$currency->name = "Dominican Peso";
		$currency->symbol_native = "RD$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "DOP";
		$currency->name_plural = "Dominican pesos";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "DA";
		$currency->name = "Algerian Dinar";
		$currency->symbol_native = "د.ج.‏";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "DZD";
		$currency->name_plural = "Algerian dinars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Ekr";
		$currency->name = "Estonian Kroon";
		$currency->symbol_native = "kr";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "EEK";
		$currency->name_plural = "Estonian kroons";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "EGP";
		$currency->name = "Egyptian Pound";
		$currency->symbol_native = "ج.م.‏";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "EGP";
		$currency->name_plural = "Egyptian pounds";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Nfk";
		$currency->name = "Eritrean Nakfa";
		$currency->symbol_native = "Nfk";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "ERN";
		$currency->name_plural = "Eritrean nakfas";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Br";
		$currency->name = "Ethiopian Birr";
		$currency->symbol_native = "Br";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "ETB";
		$currency->name_plural = "Ethiopian birrs";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "£";
		$currency->name = "British Pound Sterling";
		$currency->symbol_native = "£";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "GBP";
		$currency->name_plural = "British pounds sterling";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "GEL";
		$currency->name = "Georgian Lari";
		$currency->symbol_native = "GEL";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "GEL";
		$currency->name_plural = "Georgian laris";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "GH₵";
		$currency->name = "Ghanaian Cedi";
		$currency->symbol_native = "GH₵";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "GHS";
		$currency->name_plural = "Ghanaian cedis";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "FG";
		$currency->name = "Guinean Franc";
		$currency->symbol_native = "FG";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "GNF";
		$currency->name_plural = "Guinean francs";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "GTQ";
		$currency->name = "Guatemalan Quetzal";
		$currency->symbol_native = "Q";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "GTQ";
		$currency->name_plural = "Guatemalan quetzals";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "HK$";
		$currency->name = "Hong Kong Dollar";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "HKD";
		$currency->name_plural = "Hong Kong dollars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "HNL";
		$currency->name = "Honduran Lempira";
		$currency->symbol_native = "L";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "HNL";
		$currency->name_plural = "Honduran lempiras";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "kn";
		$currency->name = "Croatian Kuna";
		$currency->symbol_native = "kn";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "HRK";
		$currency->name_plural = "Croatian kunas";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Ft";
		$currency->name = "Hungarian Forint";
		$currency->symbol_native = "Ft";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "HUF";
		$currency->name_plural = "Hungarian forints";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Rp";
		$currency->name = "Indonesian Rupiah";
		$currency->symbol_native = "Rp";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "IDR";
		$currency->name_plural = "Indonesian rupiahs";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "₪";
		$currency->name = "Israeli New Sheqel";
		$currency->symbol_native = "₪";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "ILS";
		$currency->name_plural = "Israeli new sheqels";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Rs";
		$currency->name = "Indian Rupee";
		$currency->symbol_native = "টকা";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "INR";
		$currency->name_plural = "Indian rupees";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "IQD";
		$currency->name = "Iraqi Dinar";
		$currency->symbol_native = "د.ع.‏";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "IQD";
		$currency->name_plural = "Iraqi dinars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "IRR";
		$currency->name = "Iranian Rial";
		$currency->symbol_native = "﷼";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "IRR";
		$currency->name_plural = "Iranian rials";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Ikr";
		$currency->name = "Icelandic Króna";
		$currency->symbol_native = "kr";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "ISK";
		$currency->name_plural = "Icelandic krónur";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "J$";
		$currency->name = "Jamaican Dollar";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "JMD";
		$currency->name_plural = "Jamaican dollars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "JD";
		$currency->name = "Jordanian Dinar";
		$currency->symbol_native = "د.أ.‏";
		$currency->decimal_digits = 3;
		$currency->rounding = 0;
		$currency->code = "JOD";
		$currency->name_plural = "Jordanian dinars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "¥";
		$currency->name = "Japanese Yen";
		$currency->symbol_native = "￥";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "JPY";
		$currency->name_plural = "Japanese yen";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Ksh";
		$currency->name = "Kenyan Shilling";
		$currency->symbol_native = "Ksh";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "KES";
		$currency->name_plural = "Kenyan shillings";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "KHR";
		$currency->name = "Cambodian Riel";
		$currency->symbol_native = "៛";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "KHR";
		$currency->name_plural = "Cambodian riels";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "CF";
		$currency->name = "Comorian Franc";
		$currency->symbol_native = "FC";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "KMF";
		$currency->name_plural = "Comorian francs";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "₩";
		$currency->name = "South Korean Won";
		$currency->symbol_native = "₩";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "KRW";
		$currency->name_plural = "South Korean won";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "KD";
		$currency->name = "Kuwaiti Dinar";
		$currency->symbol_native = "د.ك.‏";
		$currency->decimal_digits = 3;
		$currency->rounding = 0;
		$currency->code = "KWD";
		$currency->name_plural = "Kuwaiti dinars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "KZT";
		$currency->name = "Kazakhstani Tenge";
		$currency->symbol_native = "тңг.";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "KZT";
		$currency->name_plural = "Kazakhstani tenges";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "LB£";
		$currency->name = "Lebanese Pound";
		$currency->symbol_native = "ل.ل.‏";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "LBP";
		$currency->name_plural = "Lebanese pounds";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "SLRs";
		$currency->name = "Sri Lankan Rupee";
		$currency->symbol_native = "SL Re";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "LKR";
		$currency->name_plural = "Sri Lankan rupees";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Lt";
		$currency->name = "Lithuanian Litas";
		$currency->symbol_native = "Lt";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "LTL";
		$currency->name_plural = "Lithuanian litai";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Ls";
		$currency->name = "Latvian Lats";
		$currency->symbol_native = "Ls";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "LVL";
		$currency->name_plural = "Latvian lati";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "LD";
		$currency->name = "Libyan Dinar";
		$currency->symbol_native = "د.ل.‏";
		$currency->decimal_digits = 3;
		$currency->rounding = 0;
		$currency->code = "LYD";
		$currency->name_plural = "Libyan dinars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "MAD";
		$currency->name = "Moroccan Dirham";
		$currency->symbol_native = "د.م.‏";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "MAD";
		$currency->name_plural = "Moroccan dirhams";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "MDL";
		$currency->name = "Moldovan Leu";
		$currency->symbol_native = "MDL";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "MDL";
		$currency->name_plural = "Moldovan lei";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "MGA";
		$currency->name = "Malagasy Ariary";
		$currency->symbol_native = "MGA";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "MGA";
		$currency->name_plural = "Malagasy Ariaries";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "MKD";
		$currency->name = "Macedonian Denar";
		$currency->symbol_native = "MKD";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "MKD";
		$currency->name_plural = "Macedonian denari";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "MMK";
		$currency->name = "Myanma Kyat";
		$currency->symbol_native = "K";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "MMK";
		$currency->name_plural = "Myanma kyats";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "MOP$";
		$currency->name = "Macanese Pataca";
		$currency->symbol_native = "MOP$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "MOP";
		$currency->name_plural = "Macanese patacas";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "MURs";
		$currency->name = "Mauritian Rupee";
		$currency->symbol_native = "MURs";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "MUR";
		$currency->name_plural = "Mauritian rupees";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "MX$";
		$currency->name = "Mexican Peso";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "MXN";
		$currency->name_plural = "Mexican pesos";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "RM";
		$currency->name = "Malaysian Ringgit";
		$currency->symbol_native = "RM";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "MYR";
		$currency->name_plural = "Malaysian ringgits";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "MTn";
		$currency->name = "Mozambican Metical";
		$currency->symbol_native = "MTn";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "MZN";
		$currency->name_plural = "Mozambican meticals";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "N$";
		$currency->name = "Namibian Dollar";
		$currency->symbol_native = "N$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "NAD";
		$currency->name_plural = "Namibian dollars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "₦";
		$currency->name = "Nigerian Naira";
		$currency->symbol_native = "₦";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "NGN";
		$currency->name_plural = "Nigerian nairas";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "C$";
		$currency->name = "Nicaraguan Córdoba";
		$currency->symbol_native = "C$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "NIO";
		$currency->name_plural = "Nicaraguan córdobas";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Nkr";
		$currency->name = "Norwegian Krone";
		$currency->symbol_native = "kr";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "NOK";
		$currency->name_plural = "Norwegian kroner";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "NPRs";
		$currency->name = "Nepalese Rupee";
		$currency->symbol_native = "नेरू";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "NPR";
		$currency->name_plural = "Nepalese rupees";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "NZ$";
		$currency->name = "New Zealand Dollar";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "NZD";
		$currency->name_plural = "New Zealand dollars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "OMR";
		$currency->name = "Omani Rial";
		$currency->symbol_native = "ر.ع.‏";
		$currency->decimal_digits = 3;
		$currency->rounding = 0;
		$currency->code = "OMR";
		$currency->name_plural = "Omani rials";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "B/.";
		$currency->name = "Panamanian Balboa";
		$currency->symbol_native = "B/.";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "PAB";
		$currency->name_plural = "Panamanian balboas";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "S/.";
		$currency->name = "Peruvian Nuevo Sol";
		$currency->symbol_native = "S/.";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "PEN";
		$currency->name_plural = "Peruvian nuevos soles";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "₱";
		$currency->name = "Philippine Peso";
		$currency->symbol_native = "₱";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "PHP";
		$currency->name_plural = "Philippine pesos";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "PKRs";
		$currency->name = "Pakistani Rupee";
		$currency->symbol_native = "₨";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "PKR";
		$currency->name_plural = "Pakistani rupees";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "zł";
		$currency->name = "Polish Zloty";
		$currency->symbol_native = "zł";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "PLN";
		$currency->name_plural = "Polish zlotys";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "₲";
		$currency->name = "Paraguayan Guarani";
		$currency->symbol_native = "₲";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "PYG";
		$currency->name_plural = "Paraguayan guaranis";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "QR";
		$currency->name = "Qatari Rial";
		$currency->symbol_native = "ر.ق.‏";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "QAR";
		$currency->name_plural = "Qatari rials";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "RON";
		$currency->name = "Romanian Leu";
		$currency->symbol_native = "RON";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "RON";
		$currency->name_plural = "Romanian lei";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "din.";
		$currency->name = "Serbian Dinar";
		$currency->symbol_native = "дин.";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "RSD";
		$currency->name_plural = "Serbian dinars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "RUB";
		$currency->name = "Russian Ruble";
		$currency->symbol_native = "₽.";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "RUB";
		$currency->name_plural = "Russian rubles";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "RWF";
		$currency->name = "Rwandan Franc";
		$currency->symbol_native = "FR";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "RWF";
		$currency->name_plural = "Rwandan francs";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "SR";
		$currency->name = "Saudi Riyal";
		$currency->symbol_native = "ر.س.‏";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "SAR";
		$currency->name_plural = "Saudi riyals";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "SDG";
		$currency->name = "Sudanese Pound";
		$currency->symbol_native = "SDG";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "SDG";
		$currency->name_plural = "Sudanese pounds";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Skr";
		$currency->name = "Swedish Krona";
		$currency->symbol_native = "kr";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "SEK";
		$currency->name_plural = "Swedish kronor";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "S$";
		$currency->name = "Singapore Dollar";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "SGD";
		$currency->name_plural = "Singapore dollars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Ssh";
		$currency->name = "Somali Shilling";
		$currency->symbol_native = "Ssh";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "SOS";
		$currency->name_plural = "Somali shillings";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "SY£";
		$currency->name = "Syrian Pound";
		$currency->symbol_native = "ل.س.‏";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "SYP";
		$currency->name_plural = "Syrian pounds";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "฿";
		$currency->name = "Thai Baht";
		$currency->symbol_native = "฿";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "THB";
		$currency->name_plural = "Thai baht";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "DT";
		$currency->name = "Tunisian Dinar";
		$currency->symbol_native = "د.ت.‏";
		$currency->decimal_digits = 3;
		$currency->rounding = 0;
		$currency->code = "TND";
		$currency->name_plural = "Tunisian dinars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "T$";
		$currency->name = "Tongan Paʻanga";
		$currency->symbol_native = "T$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "TOP";
		$currency->name_plural = "Tongan paʻanga";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "TL";
		$currency->name = "Turkish Lira";
		$currency->symbol_native = "TL";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "TRY";
		$currency->name_plural = "Turkish Lira";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "TT$";
		$currency->name = "Trinidad and Tobago Dollar";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "TTD";
		$currency->name_plural = "Trinidad and Tobago dollars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "NT$";
		$currency->name = "New Taiwan Dollar";
		$currency->symbol_native = "NT$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "TWD";
		$currency->name_plural = "New Taiwan dollars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "TSh";
		$currency->name = "Tanzanian Shilling";
		$currency->symbol_native = "TSh";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "TZS";
		$currency->name_plural = "Tanzanian shillings";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "₴";
		$currency->name = "Ukrainian Hryvnia";
		$currency->symbol_native = "₴";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "UAH";
		$currency->name_plural = "Ukrainian hryvnias";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "USh";
		$currency->name = "Ugandan Shilling";
		$currency->symbol_native = "USh";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "UGX";
		$currency->name_plural = "Ugandan shillings";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = '$U';
		$currency->name = "Uruguayan Peso";
		$currency->symbol_native = "$";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "UYU";
		$currency->name_plural = "Uruguayan pesos";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "UZS";
		$currency->name = "Uzbekistan Som";
		$currency->symbol_native = "UZS";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "UZS";
		$currency->name_plural = "Uzbekistan som";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "Bs.F.";
		$currency->name = "Venezuelan Bolívar";
		$currency->symbol_native = "Bs.F.";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "VEF";
		$currency->name_plural = "Venezuelan bolívars";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "₫";
		$currency->name = "Vietnamese Dong";
		$currency->symbol_native = "₫";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "VND";
		$currency->name_plural = "Vietnamese dong";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "FCFA";
		$currency->name = "CFA Franc BEAC";
		$currency->symbol_native = "FCFA";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "XAF";
		$currency->name_plural = "CFA francs BEAC";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "CFA";
		$currency->name = "CFA Franc BCEAO";
		$currency->symbol_native = "CFA";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "XOF";
		$currency->name_plural = "CFA francs BCEAO";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "YR";
		$currency->name = "Yemeni Rial";
		$currency->symbol_native = "ر.ي.‏";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "YER";
		$currency->name_plural = "Yemeni rials";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "R";
		$currency->name = "South African Rand";
		$currency->symbol_native = "R";
		$currency->decimal_digits = 2;
		$currency->rounding = 0;
		$currency->code = "ZAR";
		$currency->name_plural = "South African rand";
        $currency->save();

        $currency = new CurrencyMaster;
		$currency->symbol = "ZK";
		$currency->name = "Zambian Kwacha";
		$currency->symbol_native = "ZK";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "ZMK";
		$currency->name_plural = "Zambian kwachas";
        $currency->save();

        $currency = new CurrencyMaster;
        $currency->symbol = "ZWL$";
		$currency->name = "Zimbabwean Dollar";
		$currency->symbol_native = "ZWL$";
		$currency->decimal_digits = 0;
		$currency->rounding = 0;
		$currency->code = "ZWL";
		$currency->name_plural = "Zimbabwean Dollar";
        $currency->save();
    }
}
