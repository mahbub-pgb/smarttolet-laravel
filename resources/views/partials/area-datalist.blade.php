{{-- Options for the area search inputs: users can type to filter or scroll to pick. --}}
<datalist id="area-options">
    @foreach ($areas as $area)
        <option value="{{ $area }}"></option>
    @endforeach
</datalist>
