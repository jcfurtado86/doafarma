import { TextInput } from 'react-native';
import { styles } from './styles';

export function Input() {
  return (
    <view style={styles.group}>
      <TextInput style={styles.control} />
    </view>
  );
}
