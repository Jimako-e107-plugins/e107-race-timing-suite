
document.addEventListener('DOMContentLoaded', function () {

    const birthdayInput = document.getElementById('racerBirthday');
    const birthdayError = document.getElementById('birthdayError');
    const ageDisplay = document.getElementById('racerAge');
    const genderInput = document.getElementById('racerGender');

    birthdayInput.addEventListener('change', fetchCategories);
    genderInput.addEventListener('change', fetchCategories);


    // Select all radio buttons in the racer_gender group
    const genderRadios = document.querySelectorAll('input[name="racer_gender"]');

    // Attach event listeners to each radio button
    genderRadios.forEach(function (radio) {
        radio.addEventListener('change', fetchCategories);
    });


    const regex = /^(\d{1,2})\.(\d{1,2})\.(\d{4})$/;

    birthdayInput.addEventListener('input', function () {
        const birthdayInputValue = this.value;

        // Regulačný výraz pre formát d.m.yyyy alebo dd.mm.yyyy
        const regex = /^(\d{1,2})\.(\d{1,2})\.(\d{4})$/;

        if (regex.test(birthdayInputValue)) {
            // Skontrolovať, či dátum existuje
            const parts = birthdayInputValue.split('.');
            const day = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1; // Mesiace v JavaScripte sú 0-indexované
            const year = parseInt(parts[2], 10);
            const date = new Date(year, month, day);

            // Skontrolovať, či dátum neprekračuje hranice
            if (date.getDate() === day && date.getMonth() === month && date.getFullYear() === year) {
                birthdayError.style.display = 'none'; // Skryt chybovú správu
                this.classList.remove('is-invalid'); // Odstrániť invalid triedu
                updateAgeDisplay(date); // Ak je dátum správny, vypočítať a zobraziť vek
            } else {
                birthdayError.style.display = 'inline'; // Zobraziť chybovú správu
                this.classList.add('is-invalid'); // Pridať invalid triedu
                ageDisplay.textContent = ''; // Ak je dátum neplatný, zobraziť prázdne pole pre vek
            }
        } else {
            birthdayError.style.display = 'inline'; // Zobraziť chybovú správu
            this.classList.add('is-invalid'); // Pridať invalid triedu
            ageDisplay.textContent = ''; // Ak je formát neplatný, zobraziť prázdne pole pre vek
        }
    });

    // Funkcia na aktualizáciu zobrazenia veku
    function updateAgeDisplay(birthday) {
        const age = calculateAge(birthday); // Počítanie veku
        const ageDisplay = document.getElementById('racerAge'); // Prvok pre zobrazenie veku
        ageDisplay.textContent = age; // Zobrazenie vypočítaného veku
    }

    // Funkcia na výpočet veku
    function calculateAge(birthday) {
        const today = new Date();
        const birthDate = new Date(birthday); // Convert the input date to a Date object
        let age;

        // Získanie hodnoty skrytého poľa pre spôsob výpočtu veku
        const calculationMethod = document.getElementById('ageCalculationMethod').value;

        if (calculationMethod === 'startOfYear') {
            // Ak sa má vek počítať k 1. januáru aktuálneho roka
            const startOfYear = new Date(today.getFullYear(), 0, 1); // 1. január aktuálneho roka
            age = startOfYear.getFullYear() - birthDate.getFullYear();

            const monthDifference = startOfYear.getMonth() - birthDate.getMonth();
            if (monthDifference < 0 || (monthDifference === 0 && startOfYear.getDate() < birthDate.getDate())) {
                age--;
            }
        } else {
            // Ak sa má vek počítať k aktuálnemu dňu
            age = today.getFullYear() - birthDate.getFullYear();
            const monthDifference = today.getMonth() - birthDate.getMonth();

            if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
        }

        return age;
    }



    function fetchCategories() {
        const birthday = $('#racerBirthday').val();
       // const gender = $('#racerGender').val();
        const gender = $('input[name="racer_gender"]:checked').val(); // Select the checked radio
        const raceId = $('#racerRace').val();

        console.log(e107.settings); // Log the birthday value
        console.log('Gender:', gender); // Log the gender value
        console.log('Race:', raceId); // Log the gender value 

        var url = e107.settings.basePath; 

        console.log(url);
        if (birthday || gender) {
            $.ajax({
                url: url + 'eplugins/racers/racer_category_handler.php',
                type: 'POST',
                data: { birthday: birthday, gender: gender, race: raceId },
                success: function (response) {
                   // console.log('Response from server:', response); // Log the server response
                    $('#racerCategory').html(response);
                },
                error: function (xhr, status, error) {
                    // Log if the request fails
                    console.log('AJAX request failed. Status:', status, 'Error:', error);
                }
            });
        } else {
            console.log('No birthday or gender provided'); // Log if no valid input was given
        }
    }

});
