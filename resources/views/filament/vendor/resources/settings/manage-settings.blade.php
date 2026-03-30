<?php

use Filament\Forms\Form;

?>

<x-filament-panels::page>
    <x-filament-panels::form :form="$form">
        {{ $form->render() }}
    </x-filament-panels::form>
    
    <x-filament-panels::form.actions :actions="$getActions()" />
</x-filament-panels::page>
