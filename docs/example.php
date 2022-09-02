<?php
declare(strict_types=1);

function main(): void
{
    print_this_string('Hello, world!');
}

function print_this_string(string $thisString): void
{
    echo $thisString;
}
